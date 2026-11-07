<?php

namespace App\Domain\Settings;

use App\Domain\Audit\AuditLogger;
use App\Enums\RoleSlug;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Real-money features are disabled unless explicitly enabled in the database.
 * Missing flags are treated as disabled (fail closed).
 */
class FeatureFlagService
{
    /**
     * Versioned so caches written by older builds (which stored Eloquent
     * models) are ignored instead of being read back as incomplete objects.
     */
    private const CACHE_PREFIX = 'feature_flag:v2:';

    public const REAL_MONEY_DEPOSITS = 'real_money_deposits';

    public const REAL_MONEY_WITHDRAWALS = 'real_money_withdrawals';

    public const CASH_EQUIVALENT_SETTLEMENT = 'cash_equivalent_settlement';

    public const PLATFORM_FEES = 'platform_fees';

    public const REAL_MONEY_ROOMS = 'real_money_rooms';

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [
            self::REAL_MONEY_DEPOSITS,
            self::REAL_MONEY_WITHDRAWALS,
            self::CASH_EQUIVALENT_SETTLEMENT,
            self::PLATFORM_FEES,
            self::REAL_MONEY_ROOMS,
        ];
    }

    public function __construct(private readonly AuditLogger $audit) {}

    public function enabled(string $key): bool
    {
        // Cache a boolean, never the model: config('cache.serializable_classes')
        // is false in Laravel 13, so cached models come back as
        // __PHP_Incomplete_Class and flags would always fail closed.
        $enabled = Cache::rememberForever(
            $this->cacheKey($key),
            function () use ($key) {
                $flag = FeatureFlag::query()->where('key', $key)->first();

                return (bool) $flag?->enabled;
            },
        );

        return (bool) $enabled;
    }

    public function disabled(string $key): bool
    {
        return ! $this->enabled($key);
    }

    public function setEnabled(string $key, bool $enabled, ?User $actor = null): FeatureFlag
    {
        $flag = FeatureFlag::query()->firstOrNew(['key' => $key]);
        $previous = (bool) $flag->enabled;

        $flag->fill(['enabled' => $enabled, 'updated_by' => $actor?->id])->save();

        $this->audit->log(
            action: 'feature_flag.updated',
            subject: $flag,
            oldValues: ['enabled' => $previous],
            newValues: ['enabled' => $enabled],
            actor: $actor,
            actorRole: $actor?->role ?? RoleSlug::SuperAdministrator->value,
        );

        Cache::forget($this->cacheKey($key));

        return $flag;
    }

    /**
     * @return array<string, bool>
     */
    public function all(): array
    {
        return FeatureFlag::query()->pluck('enabled', 'key')
            ->map(fn ($enabled) => (bool) $enabled)
            ->all();
    }

    private function cacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }
}
