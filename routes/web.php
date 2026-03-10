<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WochenberichtController;
use App\Http\Controllers\AufgabeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

// Dashboard — rollenbasiert
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Azubi-Routen
Route::middleware(['auth', 'rolle:azubi'])->group(function () {
    // Wochenberichte
    Route::get('/wochenbericht', [WochenberichtController::class, 'index'])->name('wochenbericht.index');
    Route::get('/wochenbericht/aktuell', [WochenberichtController::class, 'show'])->name('wochenbericht.aktuell');
    Route::get('/wochenbericht/{id}', [WochenberichtController::class, 'show'])->name('wochenbericht.show');
    Route::post('/wochenbericht/{id}/speichern', [WochenberichtController::class, 'speichern'])->name('wochenbericht.speichern');
    Route::post('/wochenbericht/{id}/abgeben', [WochenberichtController::class, 'abgeben'])->name('wochenbericht.abgeben');
    Route::post('/wochenbericht/{id}/signieren', [WochenberichtController::class, 'azubiSignieren'])->name('wochenbericht.signieren');

    // Aufgaben
    Route::get('/aufgaben', [AufgabeController::class, 'index'])->name('aufgaben.index');
    Route::post('/aufgaben', [AufgabeController::class, 'store'])->name('aufgaben.store');
    Route::patch('/aufgaben/config', [AufgabeController::class, 'stundenConfigUpdate'])->name('aufgaben.config');
    Route::patch('/aufgaben/{id}', [AufgabeController::class, 'update'])->name('aufgaben.update');
    Route::delete('/aufgaben/{id}', [AufgabeController::class, 'destroy'])->name('aufgaben.destroy');
    Route::patch('/aufgaben/{id}/status', [AufgabeController::class, 'statusAendern'])->name('aufgaben.status');
    Route::patch('/aufgaben/{id}/archivieren', [AufgabeController::class, 'archivieren'])->name('aufgaben.archivieren');
    Route::patch('/aufgaben/{id}/wiederherstellen', [AufgabeController::class, 'wiederherstellen'])->name('aufgaben.wiederherstellen');
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
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('/profile/darkmode', [ProfileController::class, 'updateDarkMode'])->name('profile.darkmode');
});

require __DIR__.'/auth.php';