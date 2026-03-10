<?php

namespace App\Http\Controllers;

use App\Models\Wochenbericht;
use App\Models\WochenberichtEintrag;
use App\Models\WochenberichtBearbeitung;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WochenberichtController extends Controller
{
    // Übersicht aller Berichte des Azubis
    public function index()
    {
        $berichte = Wochenbericht::where('azubi_id', auth()->id())
            ->orderBy('startdatum', 'desc')
            ->paginate(10);

        return view('wochenbericht.index', compact('berichte'));
    }

    // Bericht erstellen oder anzeigen
    public function show($id = null)
    {
        $user = auth()->user();

        if ($id) {
            $bericht = Wochenbericht::where('azubi_id', $user->id)
                ->findOrFail($id);
        } else {
            // Aktuelle Woche
            $start = Carbon::now()->startOfWeek()->toDateString();
            $end   = Carbon::now()->endOfWeek()->subDays(2)->toDateString(); // Freitag

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

    // Eintrag speichern
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

    // Bericht abgeben
    public function abgeben($id)
    {
        $bericht = Wochenbericht::where('azubi_id', auth()->id())->findOrFail($id);

        if ($bericht->gesperrt) {
            return back()->with('error', 'Dieser Bericht ist bereits gesperrt.');
        }

        $bericht->update([
            'abgegeben'    => true,
            'abgegeben_am' => now(),
        ]);

        return back()->with('success', 'Bericht erfolgreich abgegeben.');
    }

    // Azubi signiert selbst
    public function azubiSignieren($id)
    {
        $bericht = Wochenbericht::where('azubi_id', auth()->id())->findOrFail($id);

        if (!$bericht->abgegeben) {
            return back()->with('error', 'Bericht muss zuerst abgegeben werden.');
        }

        $bericht->update([
            'azubi_signiert'    => true,
            'azubi_signiert_am' => now(),
        ]);

        return back()->with('success', 'Bericht signiert.');
    }
}