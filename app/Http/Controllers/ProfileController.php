<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'vorname'          => ['required', 'string', 'max:255'],
            'nachname'         => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'ausbildungsbeginn'=> ['nullable', 'date'],
            'ausbildungsende'  => ['nullable', 'date', 'after:ausbildungsbeginn'],
            'abteilung'        => ['nullable', 'string', 'max:255'],
        ]);

        $user->vorname           = $request->vorname;
        $user->nachname          = $request->nachname;
        $user->ausbildungsbeginn = $request->ausbildungsbeginn;
        $user->ausbildungsende   = $request->ausbildungsende;
        $user->abteilung         = $request->abteilung;
        $user->dark_mode         = $request->boolean('dark_mode');

        if ($user->email !== $request->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return Redirect::route('profile.edit')->with('status', 'password-updated');
    }

    public function updateDarkMode(Request $request): RedirectResponse
    {
        $request->user()->update([
            'dark_mode' => $request->boolean('dark_mode'),
        ]);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    public function updateSignatur(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pfad = null;

        if ($request->hasFile('signatur_bild')) {
            $pfad = $request->file('signatur_bild')->store('signaturen', 'public');
        } elseif ($request->filled('signatur_canvas')) {
            $data = $request->signatur_canvas;
            $data = str_replace('data:image/png;base64,', '', $data);
            $data = base64_decode($data);
            $dateiname = 'signaturen/' . $user->id . '_' . time() . '.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put($dateiname, $data);
            $pfad = $dateiname;
        }

        if ($pfad) {
            // Alte Signatur löschen
            if ($user->signatur_pfad) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->signatur_pfad);
            }
            $user->update(['signatur_pfad' => $pfad]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}