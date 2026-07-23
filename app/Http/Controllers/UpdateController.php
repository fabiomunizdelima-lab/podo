<?php

namespace App\Http\Controllers;

use App\Jobs\RunUpdate;
use App\Services\UpdateService;

/**
 * Controllo versione e aggiornamento applicativo (solo superadmin).
 */
class UpdateController extends Controller
{
    public function __construct(private UpdateService $svc)
    {
    }

    public function check()
    {
        return response()->json($this->svc->check());
    }

    public function start()
    {
        if ($this->svc->isRunning()) {
            return response()->json(['error' => 'Aggiornamento gia in corso.'], 409);
        }

        $this->svc->setStatus([
            'state' => 'queued',
            'step' => 0,
            'total' => count(UpdateService::STEPS),
            'label' => 'In coda',
            'log' => [],
        ]);

        RunUpdate::dispatch();

        activity('sistema')->causedBy(request()->user())->event('update_started')
            ->log('Aggiornamento applicativo avviato');

        return response()->json(['ok' => true]);
    }

    public function status()
    {
        return response()->json($this->svc->status());
    }
}
