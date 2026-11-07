<?php

namespace App\Domain\Settings;

use App\Domain\Audit\AuditLogger;
use App\Domain\Wallet\Money;
use App\Enums\RoleSlug;
use App\Models\SettingChange;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Typed access to database-backed settings. Financial values are read from here
 * and never hardcoded (agents.md §7, §10).
 */
class SettingsService
{
    /**
     * Versioned so caches written by older builds (which stored Eloquent
     * models) are ignored instead of being read back as incomplete objects.
     */
    private const CACHE_PREFIX = 'system_setting:v2:';

    public function __construct(private readonly AuditLogger $audit) {}

    public function get(string $key, mixed $default = null): mixed
    {
        // Only scalars/arrays are cached: config('cache.serializable_classes')
        // is false in Laravel 13, so caching models would unserialize them as
        // __PHP_Incomplete_Class and silently fall back to defaults.
        $cached = Cache::rememberForever($this->cacheKey($key), function () use ($key) {
            $setting = SystemSetting::query()->where('key', $key)->first();

            return [
                'exists' => $setting !== null,
                'value' => $setting?->value,
            ];
        });

        if (! ($cached['exists'] ?? false)) {
            return $default;
        }

        return $cached['value'] ?? $default;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * A monetary setting stored as minor units.
     */
    public function money(string $key, int $defaultMinor = 0, string $currency = 'PHP'): Money
    {
        return Money::fromMinor($this->integer($key, $defaultMinor), $currency);
    }

    /**
     * The configured value of one game point in minor units.
     */
    public function gamePointValue(): Money
    {
        return $this->money('game.point_value_minor', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(
        string $key,
        mixed $value,
        ?User $actor = null,
        ?string $reason = null,
        string $group = 'general',
    ): SystemSetting {
        $setting = DB::transaction(function () use ($key, $value, $actor, $reason, $group) {
            $setting = SystemSetting::query()->firstOrNew(['key' => $key]);
            $oldValue = $setting->exists ? $setting->value : null;

            $setting->fill([
                'value' => $value,
                'group' => $setting->group ?: $group,
                'type' => $this->detectType($value),
                'updated_by' => $actor?->id ?? $setting->updated_by,
            ])->save();

            SettingChange::create([
                'setting_key' => $key,
                'old_value' => ['value' => $oldValue],
                'new_value' => ['value' => $value],
                'changed_by' => $actor?->id,
                'reason' => $reason,
            ]);

            $this->audit->log(
                action: 'settings.updated',
                subject: $setting,
                oldValues: ['value' => $oldValue],
                newValues: ['value' => $value],
                actor: $actor,
                actorRole: $actor?->role ?? RoleSlug::SuperAdministrator->value,
            );

            return $setting;
        });

        Cache::forget($this->cacheKey($key));

        return $setting;
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        return SystemSetting::query()
            ->where('group', $group)
            ->where('is_sensitive', false)
            ->pluck('value', 'key')
            ->all();
    }

    public function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }

    private function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
