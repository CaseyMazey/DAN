<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Klasse;
use App\Models\Wochenbericht;
use App\Models\Einladung;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AusbilderController extends Controller
{
    // ─── Klassen-Detail ───────────────────────────────────────

    public function klasseShow($id)
    {
        $user = auth()->user();

        // Nur eigene Klassen
        $klasse = $user->klassen()->with(['azubis'])->findOrFail($id);

        $azubis = $klasse->azubis->map(function ($azubi) {
            $letzterBericht = Wochenbericht::where('azubi_id', $azubi->id)
                ->orderBy('startdatum', 'desc')
                ->first();
            return [
                'azubi'         => $azubi,
                'letzterBericht'=> $letzterBericht,
            ];
        });

        return view('ausbilder.klasse', compact('klasse', 'azubis'));
    }

    // ─── Azubi hinzufügen ─────────────────────────────────────

    public function azubiHinzufuegen(Request $request, $klasseId)
    {
        $user = auth()->user();
        $klasse = $user->klassen()->findOrFail($klasseId);

        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        // Existiert bereits ein Account mit dieser E-Mail?
        $existierenderUser = User::where('email', $email)->first();

        if ($existierenderUser) {
            if ($existierenderUser->rolle !== 'azubi') {
                return back()->with('error', 'Dieser Account ist kein Azubi.');
            }

            // Direkt der Klasse zuweisen
            $existierenderUser->update(['klasse_id' => $klasse->id]);

            return back()->with('success', "{$existierenderUser->full_name} wurde der Klasse hinzugefügt.");
        }

        // Kein Account — Einladungslink erstellen
        $bereitsEingeladen = Einladung::where('email', $email)
            ->where('klasse_id', $klasse->id)
            ->where('used_at', null)
            ->where('expires_at', '>', now())
            ->first();

        if ($bereitsEingeladen) {
            return back()->with('error', "Für {$email} existiert bereits eine ausstehende Einladung.");
        }

        $token = Str::random(64);

        $einladung = Einladung::create([
            'email'          => $email,
            'token'          => $token,
            'rolle'          => 'azubi',
            'klasse_id'      => $klasse->id,
            'eingeladen_von' => $user->id,
            'expires_at'     => Carbon::now()->addDays(7),
        ]);

        // Mail versuchen
        try {
            $link = route('register.invite', ['token' => $token]);
            Mail::send([], [], function ($message) use ($einladung, $link, $klasse) {
                $message->to($einladung->email)
                    ->subject('Einladung zu DAN 2.0')
                    ->html("
                        <h2>Du wurdest zur Klasse {$klasse->name} eingeladen</h2>
                        <p>Klicke auf den folgenden Link um dein Konto zu erstellen:</p>
                        <p><a href='{$link}'>{$link}</a></p>
                        <p>Dieser Link ist 7 Tage gültig.</p>
                    ");
            });
        } catch (\Exception $e) {
            // Mail fehlgeschlagen
        }

        $link = route('register.invite', ['token' => $token]);
        return back()->with('success', "Einladung für {$email} erstellt. Link: {$link}");
    }

    // ─── Azubi entfernen ──────────────────────────────────────

    public function azubiEntfernen(Request $request, $klasseId, $azubiId)
    {
        $user = auth()->user();
        $klasse = $user->klassen()->findOrFail($klasseId);

        $azubi = User::where('id', $azubiId)
            ->where('klasse_id', $klasse->id)
            ->firstOrFail();

        $azubi->update(['klasse_id' => null]);

        return back()->with('success', "{$azubi->full_name} wurde aus der Klasse entfernt.");
    }

    // ─── Azubi Profil (Übersicht für Ausbilder) ───────────────

    public function azubiProfil($azubiId)
    {
        $user = auth()->user();

        $azubi = User::findOrFail($azubiId);

        if ($user->rolle !== 'admin') {
            $azubiIds = $user->klassen()->with('azubis')->get()
                ->flatMap(fn($k) => $k->azubis->pluck('id'));
            if (!$azubiIds->contains($azubiId)) abort(403);
        }

        // Noten aus Kalendereinträgen
        $kalenderEintraege = \App\Models\KalenderEintrag::where('azubi_id', $azubiId)
            ->whereNotNull('note')
            ->orderBy('datum', 'desc')
            ->get();

        $notenProFach = $kalenderEintraege->groupBy('fach')->map(function ($eintraege) {
            return [
                'eintraege'    => $eintraege,
                'durchschnitt' => round($eintraege->avg('note'), 2),
            ];
        });

        // Bericht-Statistiken
        $berichte = Wochenbericht::where('azubi_id', $azubiId)
            ->orderBy('startdatum', 'desc')
            ->get();

        $stats = [
            'gesamt'     => $berichte->count(),
            'signiert'   => $berichte->where('signiert', true)->count(),
            'bearbeitet' => $berichte->where('bearbeitet', true)->count(),
            'ausstehend' => $berichte->where('signiert', false)->where('abgegeben', true)->count(),
        ];

        $jahre = $berichte->map(fn($b) => $b->startdatum->year)->unique()->sortDesc()->values();

        return view('ausbilder.azubi_profil', compact('azubi', 'notenProFach', 'berichte', 'stats', 'jahre'));
    }

    // ─── Berichte eines Azubis ────────────────────────────────

    public function azubiBerichte($azubiId)
    {
        $user = auth()->user();

        // Prüfen ob dieser Azubi in einer eigenen Klasse ist
        $azubi = User::findOrFail($azubiId);

        // Admins haben Zugriff auf alle Azubis, Ausbilder nur auf ihre eigenen Klassen
        if ($user->rolle !== 'admin') {
            $azubiIds = $user->klassen()->with('azubis')->get()
                ->flatMap(fn($k) => $k->azubis->pluck('id'));

            if (!$azubiIds->contains($azubiId)) {
                abort(403);
            }
        }
        $berichte = Wochenbericht::where('azubi_id', $azubiId)
            ->orderBy('startdatum', 'desc')
            ->get();

        return view('ausbilder.azubi_berichte', compact('azubi', 'berichte'));
    }

    // ─── Bericht ansehen & signieren ─────────────────────────

    public function berichtShow($id)
    {
        $user = auth()->user();

        $bericht = Wochenbericht::with(['eintraege', 'kommentare.autor', 'bearbeitungen.autor', 'azubi'])
            ->findOrFail($id);

        if ($user->rolle !== 'admin') {
            $azubiIds = $user->klassen()->with('azubis')->get()
                ->flatMap(fn($k) => $k->azubis->pluck('id'));
            if (!$azubiIds->contains($bericht->azubi_id)) abort(403);
        }

        $eintraege = $bericht->eintraege->groupBy('kategorie');

        return view('ausbilder.bericht_show', compact('bericht', 'eintraege'));
    }

    public function berichtSignieren($id)
    {
        $user = auth()->user();

        $bericht = Wochenbericht::findOrFail($id);

        if ($user->rolle !== 'admin') {
            $azubiIds = $user->klassen()->with('azubis')->get()
                ->flatMap(fn($k) => $k->azubis->pluck('id'));
            if (!$azubiIds->contains($bericht->azubi_id)) abort(403);
        }

        $bericht->update([
            'signiert'    => true,
            'signiert_am' => now(),
            'gesperrt'    => true,
        ]);

        return back()->with('success', 'Bericht signiert und gesperrt.');
    }

    public function berichtPdf($id)
    {
        $user = auth()->user();

        $bericht = Wochenbericht::with(['eintraege', 'azubi'])->findOrFail($id);

        if ($user->rolle !== 'admin') {
            $azubiIds = $user->klassen()->with('azubis')->get()
                ->flatMap(fn($k) => $k->azubis->pluck('id'));
            if (!$azubiIds->contains($bericht->azubi_id)) abort(403);
        }

        $azubi  = $bericht->azubi;
        $config = \App\Models\StundenConfig::where('azubi_id', $azubi->id)->first();
        $maxBetrieb      = $config ? $config->betrieb_stunden      : 32;
        $maxBerufsschule = $config ? $config->berufsschule_stunden  : 10;

        $eintraege = [
            'betrieb'      => $bericht->eintraege->filter(fn($e) => $e->kategorie === 'betrieb')->values(),
            'unterweisung' => $bericht->eintraege->filter(fn($e) => $e->kategorie === 'unterweisung')->values(),
            'berufsschule' => $bericht->eintraege->filter(fn($e) => $e->kategorie === 'berufsschule')->values(),
        ];

        $abzugBetrieb      = $bericht->eintraege->filter(fn($e) => $e->kategorie === 'unterweisung' && $e->stunden_abziehen_bei === 'betrieb')->sum('stunden');
        $abzugBerufsschule = $bericht->eintraege->filter(fn($e) => $e->kategorie === 'unterweisung' && $e->stunden_abziehen_bei === 'berufsschule')->sum('stunden');

        $stunden = [
            'betrieb'      => max(0, $maxBetrieb - $abzugBetrieb),
            'unterweisung' => $bericht->eintraege->filter(fn($e) => $e->kategorie === 'unterweisung')->sum('stunden'),
            'berufsschule' => max(0, $maxBerufsschule - $abzugBerufsschule),
        ];

        $beginn = $azubi->ausbildungsbeginn ? \Carbon\Carbon::parse($azubi->ausbildungsbeginn) : null;
        $ausbildungsjahr = $beginn
            ? (int) floor($beginn->diffInMonths($bericht->startdatum) / 12) + 1
            : '?';

        $service = new \App\Services\WochenberichtPdfService();
        $pdfContent = $service->generate($bericht, $azubi, $ausbildungsjahr, $eintraege, $stunden);

        $dateiname = 'Wochenbericht_KW' . $bericht->startdatum->format('W_Y') . '_' . $azubi->nachname . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $dateiname . '"',
        ]);
    }

    public function berichtKommentieren(Request $request, $id)
    {
        $user = auth()->user();

        $bericht = Wochenbericht::findOrFail($id);

        if ($user->rolle !== 'admin') {
            $azubiIds = $user->klassen()->with('azubis')->get()
                ->flatMap(fn($k) => $k->azubis->pluck('id'));
            if (!$azubiIds->contains($bericht->azubi_id)) abort(403);
        }

        $request->validate([
            'inhalt' => 'required|string|max:1000',
        ]);

        $bericht->kommentare()->create([
            'autor_id' => $user->id,
            'inhalt'   => $request->inhalt,
        ]);

        return back()->with('success', 'Kommentar gespeichert.');
    }
}
