<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

/**
 * Aggiornamento applicativo da Git: controllo versione e update guidato.
 * Il repo e pubblico, quindi fetch/pull avvengono in anonimo (nessuna credenziale).
 */
class UpdateService
{
    public const STATUS_KEY = 'podo.update.status';
    public const CHECK_KEY = 'podo.update.check';
    private const BRANCH = 'main';

    /** Passi dell aggiornamento, in ordine. */
    public const STEPS = [
        ['key' => 'backup',   'label' => 'Backup del database'],
        ['key' => 'pull',     'label' => 'Scaricamento aggiornamento'],
        ['key' => 'composer', 'label' => 'Aggiornamento dipendenze PHP'],
        ['key' => 'migrate',  'label' => 'Aggiornamento database'],
        ['key' => 'assets',   'label' => 'Compilazione interfaccia'],
        ['key' => 'cache',    'label' => 'Pulizia cache'],
    ];

    public function currentVersion(): string
    {
        $file = base_path('VERSION');

        return is_readable($file) ? trim((string) file_get_contents($file)) : '0.0.0';
    }

    /** Interroga il remoto: ritorna [versione, errore]. */
    public function remoteVersion(): array
    {
        $fetch = Process::path(base_path())->timeout(120)->run('git fetch --quiet origin '.self::BRANCH);
        if (! $fetch->successful()) {
            return [null, 'Impossibile contattare il repository: '.trim($fetch->errorOutput())];
        }

        $show = Process::path(base_path())->timeout(30)->run('git show origin/'.self::BRANCH.':VERSION');
        if (! $show->successful()) {
            return [null, 'Il branch '.self::BRANCH.' non contiene ancora il file VERSION.'];
        }

        return [trim($show->output()), null];
    }

    /** Esegue il controllo e memorizza l esito. */
    public function check(): array
    {
        $current = $this->currentVersion();
        [$remote, $error] = $this->remoteVersion();

        $result = [
            'current' => $current,
            'remote' => $remote,
            'available' => $remote !== null && version_compare($remote, $current, '>'),
            'error' => $error,
            'checked_at' => now()->toDateTimeString(),
        ];

        Cache::put(self::CHECK_KEY, $result, now()->addDay());

        return $result;
    }

    public function lastCheck(): ?array
    {
        return Cache::get(self::CHECK_KEY);
    }

    public function status(): array
    {
        return Cache::get(self::STATUS_KEY, [
            'state' => 'idle',
            'step' => 0,
            'total' => count(self::STEPS),
            'label' => null,
            'log' => [],
        ]);
    }

    public function setStatus(array $status): void
    {
        Cache::put(self::STATUS_KEY, $status, now()->addHours(2));
    }

    public function isRunning(): bool
    {
        return in_array($this->status()['state'] ?? 'idle', ['queued', 'running'], true);
    }

    /** Comando shell associato a ogni passo. */
    public function commandFor(string $key): string
    {
        return match ($key) {
            'backup' => 'php artisan backup:run --only-db --disable-notifications',
            'pull' => 'git pull --ff-only origin '.self::BRANCH,
            'composer' => 'composer install --no-interaction --prefer-dist --no-progress',
            'migrate' => 'php artisan migrate --force',
            'assets' => 'npm ci --no-audit --no-fund && npm run build',
            'cache' => 'php artisan optimize:clear',
            default => 'true',
        };
    }
}
