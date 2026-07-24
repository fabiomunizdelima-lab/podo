<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Impostazioni applicative persistite in DB (chiave/valore).
 * Sovrascrivono i default di config/podo.php quando presenti.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected $casts = ['is_encrypted' => 'boolean'];

    public static function get(string $key, $default = null)
    {
        $s = static::where('key', $key)->first();
        if (! $s) {
            return $default;
        }
        $v = $s->value;
        if ($s->is_encrypted && $v !== null) {
            try {
                $v = Crypt::decryptString($v);
            } catch (\Throwable $e) {
                // valore non decifrabile: ritorna default
                return $default;
            }
        }

        return $v ?? $default;
    }

    public static function set(string $key, $value, bool $encrypt = false): void
    {
        $stored = ($encrypt && $value !== null && $value !== '') ? Crypt::encryptString((string) $value) : $value;
        static::updateOrCreate(['key' => $key], ['value' => $stored, 'is_encrypted' => $encrypt]);
    }

    /** Ritorna le chiavi di un gruppo "prefix.*" come array associativo senza prefisso. */
    public static function group(string $prefix): array
    {
        return static::where('key', 'like', $prefix.'.%')->get()
            ->mapWithKeys(function ($s) use ($prefix) {
                $short = substr($s->key, strlen($prefix) + 1);
                $v = $s->value;
                if ($s->is_encrypted && $v !== null) {
                    try {
                        $v = Crypt::decryptString($v);
                    } catch (\Throwable $e) {
                        $v = null;
                    }
                }

                return [$short => $v];
            })->all();
    }

    /** Configurazione di fatturazione: default da config sovrascritti dal DB. */
    public static function billing(): array
    {
        $out = config('podo.billing');
        foreach (static::group('billing') as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $out[$k] = match ($k) {
                'withholding_enabled', 'ts_enabled' => (bool) (int) $v,
                'stamp_threshold', 'stamp_amount', 'withholding_rate' => (float) $v,
                default => $v,
            };
        }

        return $out;
    }

    /**
     * Configurazione SMTP per l'invio email: default dal .env, sovrascritti dal DB.
     * La password e cifrata a riposo (is_encrypted).
     */
    public static function mail(): array
    {
        $out = [
            'enabled' => false,
            'host' => (string) config('mail.mailers.smtp.host'),
            'port' => (int) config('mail.mailers.smtp.port'),
            'encryption' => 'tls',
            'username' => (string) config('mail.mailers.smtp.username'),
            'password' => (string) config('mail.mailers.smtp.password'),
            'from_address' => (string) config('mail.from.address'),
            'from_name' => (string) config('mail.from.name'),
        ];

        foreach (static::group('mail') as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $out[$k] = match ($k) {
                'enabled' => (bool) (int) $v,
                'port' => (int) $v,
                default => $v,
            };
        }

        return $out;
    }

    /** Configurazione Google Calendar: default da config/podo.php sovrascritti dal DB. */
    public static function google(): array
    {
        $out = config('podo.google_calendar');
        foreach (static::group('google') as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $out[$k] = $k === 'enabled' ? (bool) (int) $v : $v;
        }

        return $out;
    }

    /** Configurazione WhatsApp Cloud API: default da config/podo.php sovrascritti dal DB. */
    public static function whatsapp(): array
    {
        $out = config('podo.whatsapp');
        foreach (static::group('whatsapp') as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $out[$k] = match ($k) {
                'enabled' => (bool) (int) $v,
                'reminder_hours_before' => (int) $v,
                default => $v,
            };
        }

        return $out;
    }
}
