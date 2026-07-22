<?php

use App\Console\Commands\SendAppointmentReminders;
use Illuminate\Support\Facades\Schedule;

// Promemoria WhatsApp: controllo ogni 15 minuti
Schedule::command(SendAppointmentReminders::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Backup cifrato giornaliero (spatie/laravel-backup) — checklist: Backup automatici/cifrati
Schedule::command('backup:clean')->daily()->at('01:30');
Schedule::command('backup:run')->daily()->at('02:00');

// Pulizia log attività oltre la retention (audit trail su DB)
Schedule::command('activitylog:clean')->weekly();
