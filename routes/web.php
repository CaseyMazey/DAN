<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
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
    // Wochenberichte, Aufgaben etc. kommen hier rein
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