<?php

namespace App\Http\Controllers;

use App\Models\Wochenbericht;
use App\Models\WochenberichtEintrag;
use App\Models\WochenberichtBearbeitung;
use App\Models\Aufgabe;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class WochenberichtController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->ausbildungsbeginn) {
            $scanner = new \App\Services\LueckenscanService();
            $scanner->scan($user);
        }

        $startDerWoche = \Carbon\Carbon::now()->startOfWeek()->toDateString();
        $aktuell = Wochenbericht::where('azubi_id', $user->id)
            ->where('startdatum', $startDerWoche)
            ->with('eintraege')
            ->first();
            
        // Vorschau: entweder gewählter Bericht oder aktueller
        if ($request->filled('vorschau')) {
            $vorschau = Wochenbericht::where('azubi_id', $user->id)
                ->with('eintraege')
                ->findOrFail($request->vorschau);
        } else {
            $vorschau = $aktuell;
        }

        $aufgabenVorschau = Aufgabe::where('azubi_id', $user->id)
            ->where('archiviert', false)
            ->whereIn('status', ['in_bearbeitung', 'fertig'])
            ->get()
            ->groupBy(function($a) {
                return match($a->kategorie) {
                    'betrieb' => 'betrieb',
                    'unterweisung' => 'unterweisung',
                    'berufsschule' => 'berufsschule',
                    default => 'betrieb'
                };
            });
        // Aufgaben nur bei aktuellem Bericht anzeigen
        if ($vorschau?->id !== $aktuell?->id) {
            $aufgabenVorschau = collect();
        }

        $query = Wochenbericht::where('azubi_id', $user->id)
            ->where('startdatum', '!=', $startDerWoche)
            ->orderBy('startdatum', 'desc');

        if ($request->filled('jahr')) {
            $query->whereYear('startdatum', $request->jahr);
        }
        if ($request->filled('monat')) {
            $query->whereMonth('startdatum', $request->monat);
        }
        if ($request->filled('kw')) {
            $query->whereRaw('WEEK(startdatum, 1) = ?', [$request->kw]);
        }
        if ($request->boolean('nur_signierte')) {
            $query->where('signiert', true);
        }

        $vergangen = $query->limit(10)->get();

        $jahre = Wochenbericht::where('azubi_id', $user->id)
            ->selectRaw('YEAR(startdatum) as jahr')
            ->distinct()
            ->orderBy('jahr', 'desc')
            ->pluck('jahr');

        return view('wochenbericht.index', compact('aktuell', 'vorschau', 'vergangen', 'jahre', 'aufgabenVorschau'));
    }

    public function show($id = null)
    {
        $user = auth()->user();

        if ($id) {
            $bericht = Wochenbericht::where('azubi_id', $user->id)->findOrFail($id);
        } else {
            $start = Carbon::now()->startOfWeek()->toDateString();
            $end   = Carbon::now()->endOfWeek()->subDays(2)->toDateString();

            $bericht = Wochenbericht::firstOrCreate(
                ['azubi_id' => $user->id, 'startdatum' => $start],
                ['enddatum' => $end]
            );
        }

        $eintraege = $bericht->eintraege()->orderBy('kategorie')->get()->groupBy('kategorie');
        $kommentare = $bericht->kommentare()->with('autor')->get();
        $bearbeitungen = $bericht->bearbeitungen()->with('autor')->get();

        return view('wochenbericht.show', compact('bericht', 'eintraege', 'kommentare', 'bearbeitungen'));
    }

    public function speichern(Request $request, $id)
    {
        $bericht = Wochenbericht::where('azubi_id', auth()->id())->findOrFail($id);

        if ($bericht->gesperrt) {
            return back()->with('error', 'Dieser Bericht ist gesperrt.');
        }

        $request->validate([
            'kategorie'    => 'required|in:betrieb,unterweisung,berufsschule',
            'beschreibung' => 'required|string|max:2000',
            'stunden'      => 'required|numeric|min:0|max:99',
        ]);

        WochenberichtEintrag::create([
            'wochenbericht_id' => $bericht->id,
            'kategorie'        => $request->kategorie,
            'beschreibung'     => $request->beschreibung,
            'stunden'          => $request->stunden,
        ]);

        if ($bericht->abgegeben) {
            $request->validate([
                'bearbeitungs_kommentar' => 'required|string|max:500',
            ]);

            $bericht->update(['bearbeitet' => true]);

            WochenberichtBearbeitung::create([
                'wochenbericht_id' => $bericht->id,
                'autor_id'         => auth()->id(),
                'kommentar'        => $request->bearbeitungs_kommentar,
            ]);
        }

        return back()->with('success', 'Eintrag gespeichert.');
    }

    public function abgeben($id)
    {
        $bericht = Wochenbericht::where('azubi_id', auth()->id())->findOrFail($id);

        if ($bericht->gesperrt) {
            return back()->with('error', 'Dieser Bericht ist bereits gesperrt.');
        }

        $aufgaben = Aufgabe::where('azubi_id', auth()->id())
            ->where('archiviert', false)
            ->whereIn('status', ['in_bearbeitung', 'fertig'])
            ->get();

        foreach ($aufgaben as $aufgabe) {
            // Snapshot: Eintrag in den Bericht kopieren
            WochenberichtEintrag::create([
                'wochenbericht_id' => $bericht->id,
                'kategorie'        => $aufgabe->kategorie === 'unterweisung' ? 'unterweisung' : $aufgabe->kategorie,
                'beschreibung'     => $aufgabe->titel . ($aufgabe->beschreibung ? ': ' . $aufgabe->beschreibung : ''),
                'stunden'          => $aufgabe->stunden,
            ]);

            // Nur fertige Aufgaben archivieren
            if ($aufgabe->status === 'fertig') {
                $aufgabe->update([
                    'in_wochenbericht' => true,
                    'archiviert'       => true,
                    'archiviert_am'    => now(),
                ]);
            } else {
                // In Bearbeitung: nur als "im Bericht vermerkt" markieren, bleibt aktiv
                $aufgabe->update(['in_wochenbericht' => true]);
            }
        }

        $bericht->update([
            'abgegeben'    => true,
            'abgegeben_am' => now(),
        ]);

        return back()->with('success', 'Bericht erfolgreich abgegeben.');
    }

    public function azubiSignieren($id)
    {
        $bericht = Wochenbericht::where('azubi_id', auth()->id())->findOrFail($id);

        $bericht->update([
            'azubi_signiert'    => true,
            'azubi_signiert_am' => now(),
        ]);

        return back()->with('success', 'Bericht signiert.');
    }

    public function pdfExport($id)
    {
        $user = auth()->user();
        $bericht = Wochenbericht::where('azubi_id', $user->id)->with('eintraege')->findOrFail($id);

        $eintraege = [
            'betrieb'      => $bericht->eintraege->filter(fn($e) => $e->kategorie === 'betrieb')->values(),
            'unterweisung' => $bericht->eintraege->filter(fn($e) => $e->kategorie === 'unterweisung')->values(),
            'berufsschule' => $bericht->eintraege->filter(fn($e) => $e->kategorie === 'berufsschule')->values(),
        ];

        $stunden = [
            'betrieb'      => $bericht->eintraege->where('kategorie', 'betrieb')->sum('stunden'),
            'unterweisung' => $bericht->eintraege->where('kategorie', 'unterweisung')->sum('stunden'),
            'berufsschule' => $bericht->eintraege->where('kategorie', 'berufsschule')->sum('stunden'),
        ];

        $beginn = $user->ausbildungsbeginn ? \Carbon\Carbon::parse($user->ausbildungsbeginn) : null;
        $ausbildungsjahr = $beginn
            ? (int) floor($beginn->diffInMonths($bericht->startdatum) / 12) + 1
            : '?';

        $service = new \App\Services\WochenberichtPdfService();
        $pdfContent = $service->generate($bericht, $user, $ausbildungsjahr, $eintraege, $stunden);

        $dateiname = 'Wochenbericht_KW' . $bericht->startdatum->format('W_Y') . '_' . $user->nachname . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $dateiname . '"',
        ]);
    }
    public function fehlendErstellen()
    {
        $user = auth()->user();

        if (!$user->ausbildungsbeginn) {
            return back()->with('error', 'Kein Ausbildungsbeginn hinterlegt.');
        }

        $scanner = new \App\Services\LueckenscanService();
        $scanner->scan($user);

        return back()->with('success', 'Fehlende Berichte wurden erstellt.');
    }

    public function sammelPdf(Request $request)
    {
        $user = auth()->user();

        $ids = explode(',', $request->ids ?? '');
        $berichte = Wochenbericht::where('azubi_id', $user->id)
            ->whereIn('id', $ids)
            ->with('eintraege')
            ->orderBy('startdatum')
            ->get();

        if ($berichte->isEmpty()) {
            return back()->with('error', 'Keine Berichte ausgewählt.');
        }

        $beginn = $user->ausbildungsbeginn ? \Carbon\Carbon::parse($user->ausbildungsbeginn) : null;

        $berichteDaten = $berichte->map(function ($bericht) use ($user, $beginn) {
            $ausbildungsjahr = $beginn
                ? (int) floor($beginn->diffInMonths($bericht->startdatum) / 12) + 1
                : '?';

            return [
                'bericht'         => $bericht,
                'eintraege'       => [
                    'betrieb'      => $bericht->eintraege->where('kategorie', 'betrieb'),
                    'unterweisung' => $bericht->eintraege->where('kategorie', 'unterweisung'),
                    'berufsschule' => $bericht->eintraege->where('kategorie', 'berufsschule'),
                ],
                'stunden'         => [
                    'betrieb'      => $bericht->eintraege->where('kategorie', 'betrieb')->sum('stunden'),
                    'unterweisung' => $bericht->eintraege->where('kategorie', 'unterweisung')->sum('stunden'),
                    'berufsschule' => $bericht->eintraege->where('kategorie', 'berufsschule')->sum('stunden'),
                ],
                'azubi'           => $user,
                'ausbildungsjahr' => $ausbildungsjahr,
            ];
        });

        $pdf = Pdf::loadView('wochenbericht.pdf.sammel', [
            'berichteDaten' => $berichteDaten,
        ])->setPaper('a4', 'portrait');

        $dateiname = 'Sammel_Wochenberichte_' . $user->nachname . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($dateiname);
    }
    public function signaturLoeschen($id)
    {
        $bericht = Wochenbericht::where('azubi_id', auth()->id())->findOrFail($id);

        $bericht->update([
            'azubi_signiert'    => false,
            'azubi_signiert_am' => null,
        ]);

        return back()->with('success', 'Signatur entfernt.');
    }
}