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

    // ─── Berichte eines Azubis ────────────────────────────────

    public function azubiBerichte($azubiId)
    {
        $user = auth()->user();

        // Prüfen ob dieser Azubi in einer eigenen Klasse ist
        $azubiIds = $user->klassen()->with('azubis')->get()
            ->flatMap(fn($k) => $k->azubis->pluck('id'));

        if (!$azubiIds->contains($azubiId)) {
            abort(403);
        }

        $azubi = User::findOrFail($azubiId);
        $berichte = Wochenbericht::where('azubi_id', $azubiId)
            ->orderBy('startdatum', 'desc')
            ->get();

        return view('ausbilder.azubi_berichte', compact('azubi', 'berichte'));
    }

    // ─── Bericht ansehen & signieren ─────────────────────────

    public function berichtShow($id)
    {
        $user = auth()->user();

        $azubiIds = $user->klassen()->with('azubis')->get()
            ->flatMap(fn($k) => $k->azubis->pluck('id'));

        $bericht = Wochenbericht::with(['eintraege', 'kommentare.autor', 'bearbeitungen.autor', 'azubi'])
            ->findOrFail($id);

        if (!$azubiIds->contains($bericht->azubi_id)) {
            abort(403);
        }

        $eintraege = $bericht->eintraege->groupBy('kategorie');

        return view('ausbilder.bericht_show', compact('bericht', 'eintraege'));
    }

    public function berichtSignieren($id)
    {
        $user = auth()->user();

        $azubiIds = $user->klassen()->with('azubis')->get()
            ->flatMap(fn($k) => $k->azubis->pluck('id'));

        $bericht = Wochenbericht::findOrFail($id);

        if (!$azubiIds->contains($bericht->azubi_id)) {
            abort(403);
        }

        $bericht->update([
            'signiert'    => true,
            'signiert_am' => now(),
            'gesperrt'    => true,
        ]);

        return back()->with('success', 'Bericht signiert und gesperrt.');
    }

    public function berichtKommentieren(Request $request, $id)
    {
        $user = auth()->user();

        $azubiIds = $user->klassen()->with('azubis')->get()
            ->flatMap(fn($k) => $k->azubis->pluck('id'));

        $bericht = Wochenbericht::findOrFail($id);

        if (!$azubiIds->contains($bericht->azubi_id)) {
            abort(403);
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
