<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\ClinicalPhotoController;
use App\Http\Controllers\ClinicalRecordController;
use App\Http\Controllers\ClinicalVisitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MyRecordController;
use App\Http\Controllers\OrthosisController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ---------------- Autenticazione ----------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')->name('logout');

// ---------------- MFA ----------------
Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [MfaController::class, 'setup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'confirm'])->name('mfa.confirm');
    Route::get('/mfa/challenge', [MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/mfa/challenge', [MfaController::class, 'verify'])->name('mfa.verify');
});

// ---------------- Area autenticata (auth + MFA) ----------------
Route::middleware(['auth', 'mfa'])->group(function () {

    // Home: la dashboard reindirizza i pazienti alla loro cartella
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Portale paziente (ruolo "user"): solo la propria cartella
    Route::get('/mia-cartella', [MyRecordController::class, 'show'])->name('portal.record');

    // ---------------- Area gestionale (staff: admin + superadmin) ----------------
    Route::middleware('role:admin')->group(function () {

        // Pazienti
        Route::resource('patients', PatientController::class)->except(['destroy']);
        Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

        // Cartella clinica
        Route::get('patients/{patient}/cartella', [ClinicalRecordController::class, 'edit'])->name('patients.record.edit');
        Route::put('patients/{patient}/cartella', [ClinicalRecordController::class, 'update'])->name('patients.record.update');
        Route::get('patients/{patient}/visite/nuova', [ClinicalVisitController::class, 'create'])->name('patients.visits.create');
        Route::post('patients/{patient}/visite', [ClinicalVisitController::class, 'store'])->name('patients.visits.store');
        Route::get('visite/{visit}/modifica', [ClinicalVisitController::class, 'edit'])->name('visits.edit');
        Route::put('visite/{visit}', [ClinicalVisitController::class, 'update'])->name('visits.update');
        Route::delete('visite/{visit}', [ClinicalVisitController::class, 'destroy'])->name('visits.destroy');
        Route::post('patients/{patient}/foto', [ClinicalPhotoController::class, 'store'])->name('patients.photos.store');
        Route::get('foto/{photo}', [ClinicalPhotoController::class, 'show'])->name('photos.show');
        Route::delete('foto/{photo}', [ClinicalPhotoController::class, 'destroy'])->name('photos.destroy');

        // Ortesi
        Route::get('ortesi', [OrthosisController::class, 'index'])->name('orthoses.index');
        Route::get('ortesi/nuova', [OrthosisController::class, 'create'])->name('orthoses.create');
        Route::post('ortesi', [OrthosisController::class, 'store'])->name('orthoses.store');
        Route::get('ortesi/{orthosis}/modifica', [OrthosisController::class, 'edit'])->name('orthoses.edit');
        Route::put('ortesi/{orthosis}', [OrthosisController::class, 'update'])->name('orthoses.update');
        Route::post('ortesi/{orthosis}/stato', [OrthosisController::class, 'updateStatus'])->name('orthoses.status');
        Route::delete('ortesi/{orthosis}', [OrthosisController::class, 'destroy'])->name('orthoses.destroy');

        // Listino
        Route::get('listino', [TreatmentController::class, 'index'])->name('treatments.index');
        Route::resource('listino', TreatmentController::class)
            ->except(['index', 'show'])
            ->parameters(['listino' => 'treatment'])
            ->names('treatments');

        // Fatturazione
        Route::get('fatture', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('fatture/nuova', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('fatture', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('fatture/export/ts', [InvoiceController::class, 'tsExport'])->name('invoices.ts');
        Route::post('visite/{visit}/fattura', [InvoiceController::class, 'fromVisit'])->name('invoices.from_visit');
        Route::get('fatture/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('fatture/{invoice}/modifica', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('fatture/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('fatture/{invoice}/emetti', [InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('fatture/{invoice}/paga', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
        Route::get('fatture/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('fatture/{invoice}/xml', [InvoiceController::class, 'xml'])->name('invoices.xml');
        Route::delete('fatture/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

        // Agenda
        Route::get('/agenda', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/agenda/feed', [AppointmentController::class, 'feed'])->name('appointments.feed');
        Route::post('/agenda', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::put('/agenda/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
        Route::delete('/agenda/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
        Route::post('/agenda/{appointment}/reminder', [AppointmentController::class, 'sendReminder'])->name('appointments.reminder');

        // Google Calendar OAuth
        Route::get('/oauth/google/redirect', [GoogleOAuthController::class, 'redirect'])->name('google.redirect');
        Route::get('/oauth/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');

        // Amministrazione: gestione utenti + audit (admin e superadmin)
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('amministrazione/audit', [AuditController::class, 'index'])->name('audit.index');

        // Impostazioni studio / fatturazione: solo superadmin
        Route::middleware('role:superadmin')->group(function () {
            Route::get('impostazioni', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('impostazioni', [SettingsController::class, 'update'])->name('settings.update');

            // Aggiornamento applicativo
            Route::get('impostazioni/aggiornamenti/controlla', [UpdateController::class, 'check'])->name('update.check');
            Route::post('impostazioni/aggiornamenti/avvia', [UpdateController::class, 'start'])->name('update.start');
            Route::get('impostazioni/aggiornamenti/stato', [UpdateController::class, 'status'])->name('update.status');
        });
    });
});
