<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Klasse;
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
        $nutzer = User::with('klasse')->orderBy('rolle')->orderBy('nachname')->get();
        $klassen = Klasse::where('aktiv', true)->orderBy('name')->get();

        return view('admin.nutzer', compact('nutzer', 'klassen'));
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

    // ─── Klassen ──────────────────────────────────────────────

    public function klassen()
    {
        $klassen = Klasse::with(['azubis', 'ausbilder'])->orderBy('name')->get();
        $ausbilder = User::where('rolle', 'ausbilder')->where('aktiv', true)->orderBy('nachname')->get();

        return view('admin.klassen', compact('klassen', 'ausbilder'));
    }

    public function klasseErstellen(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'jahrgang' => 'nullable|string|max:50',
        ]);

        Klasse::create([
            'name'     => $request->name,
            'jahrgang' => $request->jahrgang,
        ]);

        return back()->with('success', 'Klasse erstellt.');
    }

    public function klasseUpdate(Request $request, $id)
    {
        $klasse = Klasse::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'jahrgang' => 'nullable|string|max:50',
            'aktiv'    => 'boolean',
        ]);

        $klasse->update([
            'name'     => $request->name,
            'jahrgang' => $request->jahrgang,
            'aktiv'    => $request->boolean('aktiv'),
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
            ->orderBy('created_at', 'desc')
            ->get();
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

        // Mail versuchen, aber bei Fehler trotzdem weitermachen
        try {
            $link = route('register.invite', ['token' => $token]);
            Mail::send([], [], function ($message) use ($einladung, $link) {
                $message->to($einladung->email)
                    ->subject('Einladung zu DAN 2.0')
                    ->html("
                        <h2>Du wurdest zu DAN 2.0 eingeladen</h2>
                        <p><a href='{$link}'>{$link}</a></p>
                        <p>Dieser Link ist 7 Tage gültig.</p>
                    ");
            });
        } catch (\Exception $e) {
            // Mail fehlgeschlagen, aber Einladung wurde gespeichert
        }

        return back()->with('success', "Einladung für {$request->email} erstellt. Link: " . route('register.invite', ['token' => $token]));
    }

    public function einladungLoeschen($id)
    {
        $einladung = Einladung::findOrFail($id);
        $einladung->delete();

        return back()->with('success', 'Einladung gelöscht.');
    }

}
