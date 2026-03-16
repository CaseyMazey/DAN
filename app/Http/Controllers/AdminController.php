<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Klasse;
use App\Models\Abteilung;
use App\Models\Einladung;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AdminController extends Controller
{
    // ─── Nutzer ───────────────────────────────────────────────

    public function nutzer()
    {
        $nutzer      = User::with(['klasse.abteilung'])->aktiv()->orderBy('rolle')->orderBy('nachname')->get();
        $archivierte = User::with(['klasse.abteilung'])->archiviert()->orderBy('nachname')->get();
        $klassen     = Klasse::orderBy('name')->get();
        $abteilungen = Abteilung::where('aktiv', true)->orderBy('name')->get();

        return view('admin.nutzer', compact('nutzer', 'archivierte', 'klassen', 'abteilungen'));
    }

    public function nutzerUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'rolle'    => 'required|in:azubi,ausbilder,admin',
            'aktiv'    => 'boolean',
            'klasse_id'=> 'nullable|exists:klassen,id',
        ]);

        $user->update([
            'rolle'     => $request->rolle,
            'aktiv'     => $request->boolean('aktiv'),
            'klasse_id' => $request->rolle === 'azubi' ? $request->klasse_id : null,
        ]);

        return back()->with('success', 'Nutzer aktualisiert.');
    }

    public function nutzerLoeschen($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Du kannst dich nicht selbst löschen.');
        }

        $user->delete();

        return back()->with('success', 'Nutzer gelöscht.');
    }

    // ─── Archivierung ─────────────────────────────────────────

    public function nutzerArchivieren($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Du kannst dich nicht selbst archivieren.');
        }

        $user->update([
            'archiviert'    => true,
            'archiviert_am' => now(),
            'aktiv'         => false,
        ]);

        return back()->with('success', "{$user->full_name} wurde archiviert.");
    }

    public function nutzerWiederherstellen($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'archiviert'    => false,
            'archiviert_am' => null,
            'aktiv'         => true,
        ]);

        return back()->with('success', "{$user->full_name} wurde wiederhergestellt.");
    }

    public function klasseArchivieren($id)
    {
        $klasse = Klasse::with('azubis')->findOrFail($id);

        $klasse->azubis()->update([
            'archiviert'    => true,
            'archiviert_am' => now(),
            'aktiv'         => false,
        ]);

        $klasse->update(['aktiv' => false]);

        $anzahl = $klasse->azubis->count();
        return back()->with('success', "Klasse \"{$klasse->name}\" und {$anzahl} Azubi(s) archiviert.");
    }

    public function jahrgangArchivieren(Request $request)
    {
        $request->validate(['jahrgang' => 'required|string']);

        $klassen = Klasse::where('jahrgang', $request->jahrgang)->with('azubis')->get();

        if ($klassen->isEmpty()) {
            return back()->with('error', "Kein Jahrgang \"{$request->jahrgang}\" gefunden.");
        }

        $azubiCount = 0;
        foreach ($klassen as $klasse) {
            $klasse->azubis()->update([
                'archiviert'    => true,
                'archiviert_am' => now(),
                'aktiv'         => false,
            ]);
            $azubiCount += $klasse->azubis->count();
            $klasse->update(['aktiv' => false]);
        }

        return back()->with('success', "Jahrgang {$request->jahrgang}: {$klassen->count()} Klasse(n) und {$azubiCount} Azubi(s) archiviert.");
    }

    // ─── Abteilungen ──────────────────────────────────────────

    public function abteilungen()
    {
        $abteilungen = Abteilung::withCount('klassen')->orderBy('name')->get();
        return view('admin.abteilungen', compact('abteilungen'));
    }

    public function abteilungErstellen(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:abteilungen,name',
            'kuerzel'     => 'nullable|string|max:20|unique:abteilungen,kuerzel',
            'beschreibung'=> 'nullable|string|max:1000',
        ]);

        Abteilung::create([
            'name'        => $request->name,
            'kuerzel'     => $request->kuerzel,
            'beschreibung'=> $request->beschreibung,
            'aktiv'       => true,
        ]);

        return back()->with('success', 'Abteilung erstellt.');
    }

    public function abteilungUpdate(Request $request, $id)
    {
        $abteilung = Abteilung::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255|unique:abteilungen,name,' . $id,
            'kuerzel'     => 'nullable|string|max:20|unique:abteilungen,kuerzel,' . $id,
            'beschreibung'=> 'nullable|string|max:1000',
            'aktiv'       => 'boolean',
        ]);

        $abteilung->update([
            'name'        => $request->name,
            'kuerzel'     => $request->kuerzel,
            'beschreibung'=> $request->beschreibung,
            'aktiv'       => $request->boolean('aktiv'),
        ]);

        return back()->with('success', 'Abteilung aktualisiert.');
    }

    public function abteilungLoeschen($id)
    {
        $abteilung = Abteilung::findOrFail($id);

        if ($abteilung->klassen()->exists()) {
            return back()->with('error', 'Abteilung kann nicht gelöscht werden, da ihr noch Klassen zugeordnet sind.');
        }

        $abteilung->delete();
        return back()->with('success', 'Abteilung gelöscht.');
    }

    // ─── Klassen ──────────────────────────────────────────────

    public function klassen()
    {
        $klassen     = Klasse::with(['azubis', 'ausbilder', 'abteilung'])->orderBy('name')->get();
        $ausbilder   = User::where('rolle', 'ausbilder')->aktiv()->orderBy('nachname')->get();
        $abteilungen = Abteilung::where('aktiv', true)->orderBy('name')->get();

        return view('admin.klassen', compact('klassen', 'ausbilder', 'abteilungen'));
    }

    public function klasseErstellen(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'jahrgang'    => 'nullable|string|max:50',
            'abteilung_id'=> 'nullable|exists:abteilungen,id',
        ]);

        Klasse::create([
            'name'        => $request->name,
            'jahrgang'    => $request->jahrgang,
            'abteilung_id'=> $request->abteilung_id,
        ]);

        return back()->with('success', 'Klasse erstellt.');
    }

    public function klasseUpdate(Request $request, $id)
    {
        $klasse = Klasse::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255',
            'jahrgang'    => 'nullable|string|max:50',
            'aktiv'       => 'boolean',
            'abteilung_id'=> 'nullable|exists:abteilungen,id',
        ]);

        $klasse->update([
            'name'        => $request->name,
            'jahrgang'    => $request->jahrgang,
            'aktiv'       => $request->boolean('aktiv'),
            'abteilung_id'=> $request->abteilung_id,
        ]);

        return back()->with('success', 'Klasse aktualisiert.');
    }

    public function klasseAusbilderZuweisen(Request $request, $id)
    {
        $klasse = Klasse::findOrFail($id);

        $request->validate([
            'ausbilder_ids'   => 'nullable|array',
            'ausbilder_ids.*' => 'exists:users,id',
        ]);

        $klasse->ausbilder()->sync($request->ausbilder_ids ?? []);
        return back()->with('success', 'Ausbilder zugewiesen.');
    }

    public function klasseLoeschen($id)
    {
        $klasse = Klasse::findOrFail($id);
        $klasse->delete();
        return back()->with('success', 'Klasse gelöscht.');
    }

    // ─── Einladungen ──────────────────────────────────────────

    public function einladungen()
    {
        $einladungen = Einladung::with(['klasse', 'eingeladenVon'])
            ->orderBy('created_at', 'desc')->get();
        $klassen = Klasse::where('aktiv', true)->orderBy('name')->get();

        return view('admin.einladungen', compact('einladungen', 'klassen'));
    }

    public function einladungSenden(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|unique:users,email|unique:einladungen,email',
            'rolle'    => 'required|in:azubi,ausbilder,admin',
            'klasse_id'=> 'nullable|exists:klassen,id',
        ]);

        $token = Str::random(64);

        $einladung = Einladung::create([
            'email'          => $request->email,
            'token'          => $token,
            'rolle'          => $request->rolle,
            'klasse_id'      => $request->rolle === 'azubi' ? $request->klasse_id : null,
            'eingeladen_von' => auth()->id(),
            'expires_at'     => Carbon::now()->addDays(7),
        ]);

        try {
            $link = route('register.invite', ['token' => $token]);
            Mail::send([], [], function ($message) use ($einladung, $link) {
                $message->to($einladung->email)
                    ->subject('Einladung zu DAN 2.0')
                    ->html("<h2>Du wurdest zu DAN 2.0 eingeladen</h2><p><a href='{$link}'>{$link}</a></p><p>Dieser Link ist 7 Tage gültig.</p>");
            });
        } catch (\Exception $e) {}

        return back()->with('success', "Einladung für {$request->email} erstellt. Link: " . route('register.invite', ['token' => $token]));
    }

    public function einladungLoeschen($id)
    {
        $einladung = Einladung::findOrFail($id);
        $einladung->delete();
        return back()->with('success', 'Einladung gelöscht.');
    }
}
