<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WochenberichtController;

Route::get('/', function () {
    return redirect('/login');
});

// Dashboard — rollenbasiert
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Azubi-Routen
Route::middleware(['auth', 'rolle:azubi'])->group(function () {
    Route::get('/wochenbericht', [WochenberichtController::class, 'index'])->name('wochenbericht.index');
    Route::get('/wochenbericht/aktuell', [WochenberichtController::class, 'show'])->name('wochenbericht.aktuell');
    Route::get('/wochenbericht/{id}', [WochenberichtController::class, 'show'])->name('wochenbericht.show');
    Route::post('/wochenbericht/{id}/speichern', [WochenberichtController::class, 'speichern'])->name('wochenbericht.speichern');
    Route::post('/wochenbericht/{id}/abgeben', [WochenberichtController::class, 'abgeben'])->name('wochenbericht.abgeben');
    Route::post('/wochenbericht/{id}/signieren', [WochenberichtController::class, 'azubiSignieren'])->name('wochenbericht.signieren');
});

// Ausbilder-Routen
Route::middleware(['auth', 'rolle:ausbilder'])->group(function () {
    // Ausbilder-Features kommen hier rein
});

// Admin-Routen
Route::middleware(['auth', 'rolle:admin'])->group(function () {
    // Admin-Features kommen hier rein
});

// Profil (alle eingeloggten Nutzer)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';