<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\UpdateService;
use Illuminate\Http\Request;

/**
 * Impostazioni dello studio (ex "Utility applicazione" di SmartPodos).
 * Riservato al superadmin.
 */
class SettingsController extends Controller
{
    private const TEXT_KEYS = [
        'studio_name', 'vat_number', 'fiscal_code', 'regime', 'tax_regime_code',
        'address', 'city', 'cap', 'province', 'pec', 'sdi_code', 'register_note', 'ts_default_type',
    ];

    public function edit(UpdateService $updateSvc)
    {
        $billing = Setting::billing();
        $security = config('podo.security');
        $integrations = [
            'whatsapp' => config('podo.whatsapp.enabled'),
            'google_calendar' => config('podo.google_calendar.enabled'),
        ];
        $update = [
            'current' => $updateSvc->currentVersion(),
            'last' => $updateSvc->lastCheck(),
            'running' => $updateSvc->isRunning(),
        ];

        return view('settings.index', compact('billing', 'security', 'integrations', 'update'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'studio_name' => ['nullable', 'string', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'fiscal_code' => ['nullable', 'string', 'max:16'],
            'regime' => ['nullable', 'in:forfettario,ordinario'],
            'tax_regime_code' => ['nullable', 'string', 'max:4'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'cap' => ['nullable', 'string', 'max:10'],
            'province' => ['nullable', 'string', 'max:4'],
            'pec' => ['nullable', 'string', 'max:150'],
            'sdi_code' => ['nullable', 'string', 'max:7'],
            'register_note' => ['nullable', 'string', 'max:500'],
            'ts_default_type' => ['nullable', 'string', 'max:8'],
            'withholding_enabled' => ['sometimes', 'boolean'],
            'withholding_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (empty($data['tax_regime_code']) && ! empty($data['regime'])) {
            $data['tax_regime_code'] = $data['regime'] === 'forfettario' ? 'RF19' : 'RF01';
        }

        foreach (self::TEXT_KEYS as $k) {
            Setting::set('billing.'.$k, $data[$k] ?? null);
        }
        Setting::set('billing.withholding_enabled', $request->boolean('withholding_enabled') ? '1' : '0');
        Setting::set('billing.withholding_rate', $data['withholding_rate'] ?? null);

        return redirect()->route('settings.edit')->with('success', 'Impostazioni salvate.');
    }
}
