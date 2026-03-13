<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Einladung;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterInviteController extends Controller
{
    public function show($token)
    {
        $einladung = Einladung::where('token', $token)->firstOrFail();

        if (!$einladung->istGueltig()) {
            return view('auth.invite-invalid');
        }

        return view('auth.register-invite', compact('einladung', 'token'));
    }

    public function register(Request $request, $token)
    {
        $einladung = Einladung::where('token', $token)->firstOrFail();

        if (!$einladung->istGueltig()) {
            return redirect()->route('login')->with('error', 'Dieser Einladungslink ist nicht mehr gültig.');
        }

        $request->validate([
            'vorname'              => 'required|string|max:255',
            'nachname'             => 'required|string|max:255',
            'password'             => 'required|confirmed|min:8',
            'ausbildungsbeginn'    => 'nullable|date',
            'ausbildungsende'      => 'nullable|date|after:ausbildungsbeginn',
        ]);

        $user = User::create([
            'vorname'           => $request->vorname,
            'nachname'          => $request->nachname,
            'email'             => $einladung->email,
            'password'          => Hash::make($request->password),
            'rolle'             => $einladung->rolle,
            'klasse_id'         => $einladung->klasse_id,
            'aktiv'             => true,
            'ausbildungsbeginn' => $einladung->rolle === 'azubi' ? $request->ausbildungsbeginn : null,
            'ausbildungsende'   => $einladung->rolle === 'azubi' ? $request->ausbildungsende : null,
        ]);

        $einladung->update(['used_at' => now()]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Willkommen bei DAN 2.0!');
    }
}
