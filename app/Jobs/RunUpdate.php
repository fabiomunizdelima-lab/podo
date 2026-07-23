<?php

namespace App\Jobs;

use App\Services\UpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;

/**
 * Esegue l aggiornamento applicativo passo per passo,
 * pubblicando l avanzamento in cache per la barra di progresso.
 */
class RunUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function handle(UpdateService $svc): void
    {
        $steps = UpdateService::STEPS;
        $total = count($steps);
        $log = [];

        $svc->setStatus(['state' => 'running', 'step' => 0, 'total' => $total, 'label' => 'Avvio aggiornamento', 'log' => $log]);

        foreach ($steps as $i => $step) {
            $svc->setStatus([
                'state' => 'running',
                'step' => $i,
                'total' => $total,
                'label' => $step['label'],
                'log' => $log,
            ]);

            $result = Process::path(base_path())
                ->env(['HOME' => '/tmp', 'COMPOSER_HOME' => '/tmp/composer', 'NPM_CONFIG_CACHE' => '/tmp/npm'])
                ->timeout(900)
                ->run($svc->commandFor($step['key']));

            $output = trim($result->output()."\n".$result->errorOutput());
            $tail = collect(preg_split('/\r?\n/', $output))
                ->filter(fn ($l) => trim($l) !== '')
                ->take(-4)->values()->all();

            $log[] = ($result->successful() ? 'OK  ' : 'ERR ').$step['label'];
            $log = array_merge($log, array_map(fn ($l) => '    '.$l, $tail));

            if (! $result->successful()) {
                $svc->setStatus([
                    'state' => 'error',
                    'step' => $i,
                    'total' => $total,
                    'label' => 'Errore durante: '.$step['label'],
                    'log' => $log,
                ]);

                return;
            }
        }

        $svc->setStatus([
            'state' => 'done',
            'step' => $total,
            'total' => $total,
            'label' => 'Aggiornamento completato',
            'version' => $svc->currentVersion(),
            'log' => $log,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        app(UpdateService::class)->setStatus([
            'state' => 'error',
            'step' => 0,
            'total' => count(UpdateService::STEPS),
            'label' => 'Aggiornamento interrotto',
            'log' => [substr($e->getMessage(), 0, 300)],
        ]);
    }
}
