<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WochenberichtController;
use App\Http\Controllers\AufgabeController;
use App\Http\Controllers\KalenderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterInviteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AusbilderController;

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
    Route::delete('/wochenbericht/{id}/signatur', [WochenberichtController::class, 'signaturLoeschen'])
    ->name('wochenbericht.signatur.loeschen');
    Route::get('/wochenbericht/{id}/pdf', [WochenberichtController::class, 'pdfExport'])->name('wochenbericht.pdf');
    Route::post('/wochenbericht/fehlend-erstellen', [WochenberichtController::class, 'fehlendErstellen'])->name('wochenbericht.fehlend');
    Route::post('/wochenbericht/sammel-pdf', [WochenberichtController::class, 'sammelPdf'])->name('wochenbericht.sammel');

    // Kalender
    Route::post('/kalender/fach', [KalenderController::class, 'fachErstellen'])->name('kalender.fach.store');
    Route::delete('/kalender/fach/{id}', [KalenderController::class, 'fachLoeschen'])->name('kalender.fach.destroy');
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender.index');
    Route::post('/kalender', [KalenderController::class, 'store'])->name('kalender.store');
    Route::patch('/kalender/{id}', [KalenderController::class, 'update'])->name('kalender.update');
    Route::delete('/kalender/{id}', [KalenderController::class, 'destroy'])->name('kalender.destroy');

    // Aufgaben
    Route::get('/aufgaben', [AufgabeController::class, 'index'])->name('aufgaben.index');
    Route::post('/aufgaben', [AufgabeController::class, 'store'])->name('aufgaben.store');
    Route::patch('/aufgaben/config', [AufgabeController::class, 'stundenConfigUpdate'])->name('aufgaben.config');
    Route::patch('/aufgaben/{id}', [AufgabeController::class, 'update'])->name('aufgaben.update');
    Route::delete('/aufgaben/{id}', [AufgabeController::class, 'destroy'])->name('aufgaben.destroy');
    Route::patch('/aufgaben/{id}/status', [AufgabeController::class, 'statusAendern'])->name('aufgaben.status');
    Route::patch('/aufgaben/{id}/archivieren', [AufgabeController::class, 'archivieren'])->name('aufgaben.archivieren');
    Route::patch('/aufgaben/{id}/wiederherstellen', [AufgabeController::class, 'wiederherstellen'])->name('aufgaben.wiederherstellen');

    Route::patch('/profile/signatur', [ProfileController::class, 'updateSignatur'])->name('profile.signatur');
});

// Ausbilder-Routen
Route::middleware(['auth', 'rolle:ausbilder,admin'])->prefix('ausbilder')->name('ausbilder.')->group(function () {
 
    // Klassen
    Route::get('/klasse/{id}', [AusbilderController::class, 'klasseShow'])->name('klasse.show');
    Route::post('/klasse/{id}/azubi', [AusbilderController::class, 'azubiHinzufuegen'])->name('klasse.azubi.hinzufuegen');
    Route::delete('/klasse/{klasseId}/azubi/{azubiId}', [AusbilderController::class, 'azubiEntfernen'])->name('klasse.azubi.entfernen');
 
    // Berichte
    Route::get('/azubi/{azubiId}/berichte', [AusbilderController::class, 'azubiBerichte'])->name('azubi.berichte');
    Route::get('/bericht/{id}', [AusbilderController::class, 'berichtShow'])->name('bericht.show');
    Route::post('/bericht/{id}/signieren', [AusbilderController::class, 'berichtSignieren'])->name('bericht.signieren');
    Route::post('/bericht/{id}/kommentar', [AusbilderController::class, 'berichtKommentieren'])->name('bericht.kommentieren');
});

// Admin Routen 
Route::middleware(['auth', 'rolle:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Nutzer
    Route::get('/nutzer', [AdminController::class, 'nutzer'])->name('nutzer');
    Route::patch('/nutzer/{id}', [AdminController::class, 'nutzerUpdate'])->name('nutzer.update');
    Route::delete('/nutzer/{id}', [AdminController::class, 'nutzerLoeschen'])->name('nutzer.loeschen');

    // Klassen
    Route::get('/klassen', [AdminController::class, 'klassen'])->name('klassen');
    Route::post('/klassen', [AdminController::class, 'klasseErstellen'])->name('klassen.erstellen');
    Route::patch('/klassen/{id}', [AdminController::class, 'klasseUpdate'])->name('klassen.update');
    Route::patch('/klassen/{id}/ausbilder', [AdminController::class, 'klasseAusbilderZuweisen'])->name('klassen.ausbilder');
    Route::delete('/klassen/{id}', [AdminController::class, 'klasseLoeschen'])->name('klassen.loeschen');

    // Einladungen
    Route::get('/einladungen', [AdminController::class, 'einladungen'])->name('einladungen');
    Route::post('/einladungen', [AdminController::class, 'einladungSenden'])->name('einladungen.senden');
    Route::delete('/einladungen/{id}', [AdminController::class, 'einladungLoeschen'])->name('einladungen.loeschen');
});

// Profil (alle eingeloggten Nutzer)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('/profile/darkmode', [ProfileController::class, 'updateDarkMode'])->name('profile.darkmode');
});


Route::get('/einladung/{token}', [RegisterInviteController::class, 'show'])->name('register.invite');
Route::post('/einladung/{token}', [RegisterInviteController::class, 'register'])->name('register.invite.submit');

require __DIR__.'/auth.php';