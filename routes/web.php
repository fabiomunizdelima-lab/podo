<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ---------------- Autenticazione ----------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')->name('logout');

// ---------------- MFA (utente autenticato, prima del gate MFA) ----------------
Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [MfaController::class, 'setup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'confirm'])->name('mfa.confirm');
    Route::get('/mfa/challenge', [MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/mfa/challenge', [MfaController::class, 'verify'])->name('mfa.verify');
});

// ---------------- Area applicativa (auth + MFA imposta) ----------------
Route::middleware(['auth', 'mfa'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Pazienti — tutti i ruoli operativi
    Route::resource('patients', PatientController::class)->except(['destroy']);
    // Archiviazione paziente riservata ad admin/superadmin
    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])
        ->middleware('role:admin')->name('patients.destroy');

    // Agenda / Appuntamenti
    Route::get('/agenda', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/agenda/feed', [AppointmentController::class, 'feed'])->name('appointments.feed');
    Route::post('/agenda', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/agenda/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/agenda/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    Route::post('/agenda/{appointment}/reminder', [AppointmentController::class, 'sendReminder'])->name('appointments.reminder');

    // Google Calendar OAuth
    Route::get('/oauth/google/redirect', [GoogleOAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/oauth/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');

    // Gestione utenti — solo superadmin
    Route::middleware('role:superadmin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
