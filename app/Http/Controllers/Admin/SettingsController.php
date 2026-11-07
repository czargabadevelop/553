<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\FeatureFlagService;
use App\Domain\Settings\SettingsService;
use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(FeatureFlagService $flags): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => SystemSetting::query()
                ->where('is_sensitive', false)
                ->orderBy('group')
                ->orderBy('key')
                ->get()
                ->map(fn (SystemSetting $setting) => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'description' => $setting->description,
                ]),
            'flags' => FeatureFlag::query()->orderBy('key')->get()->map(fn (FeatureFlag $flag) => [
                'key' => $flag->key,
                'enabled' => $flag->enabled,
                'description' => $flag->description,
            ]),
        ]);
    }

    public function update(Request $request, SettingsService $settings, FeatureFlagService $flags): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = SystemSetting::query()->where('key', $key)->first();

            if ($setting === null || $setting->is_sensitive) {
                continue;
            }

            $cast = match ($setting->type) {
                'integer' => (int) $value,
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
                default => $value,
            };

            $settings->set($key, $cast, $request->user(), $validated['reason'] ?? null, $setting->group);
        }

        return back()->with('success', 'Settings updated and audited.');
    }

    public function updateFlags(Request $request, FeatureFlagService $flags): RedirectResponse
    {
        $validated = $request->validate([
            'flags' => ['required', 'array'],
            'flags.*' => ['boolean'],
        ]);

        foreach ($validated['flags'] as $key => $enabled) {
            $flags->setEnabled($key, (bool) $enabled, $request->user());
        }

        return back()->with('success', 'Feature flags updated.');
    }
}
