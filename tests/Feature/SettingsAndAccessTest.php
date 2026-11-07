<?php

use App\Domain\Settings\FeatureFlagService;
use App\Domain\Settings\SettingsService;
use App\Enums\Permission as PermissionSlug;
use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\SettingChange;
use App\Models\User;
use Database\Seeders\FeatureFlagSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, SettingsSeeder::class, FeatureFlagSeeder::class]);
});

function userWithRole(RoleSlug $role): User
{
    $user = User::factory()->create(['username' => 'u'.fake()->unique()->numberBetween(1, 99999)]);

    $user->roles()->attach(Role::query()->where('slug', $role->value)->firstOrFail());

    return $user;
}

test('the five roles and their permissions are seeded', function () {
    expect(Role::query()->count())->toBe(5);

    $finance = Role::query()->where('slug', RoleSlug::FinanceAdministrator->value)->firstOrFail();

    expect($finance->hasPermission(PermissionSlug::ReviewDeposits))->toBeTrue();
    expect($finance->hasPermission(PermissionSlug::ManageRooms))->toBeFalse();
    expect($finance->hasPermission(PermissionSlug::ManageAdministrators))->toBeFalse();
});

test('super administrators hold every permission and other roles do not', function () {
    $admin = userWithRole(RoleSlug::SuperAdministrator);
    $support = userWithRole(RoleSlug::SupportAdministrator);

    expect($admin->hasPermission(PermissionSlug::AdjustWallets))->toBeTrue();
    expect($support->hasPermission(PermissionSlug::AdjustWallets))->toBeFalse();
    expect($support->hasPermission(PermissionSlug::ViewPlayers))->toBeTrue();
});

test('the role middleware blocks unauthorized roles with a 403', function () {
    Route::middleware(['web', 'auth', 'role:super_administrator'])->get('/test-admin-only', fn () => 'ok');

    $this->actingAs(User::factory()->create())->get('/test-admin-only')->assertForbidden();
    $this->actingAs(userWithRole(RoleSlug::SuperAdministrator))->get('/test-admin-only')->assertOk();
});

test('settings are cached, typed and changes are audited', function () {
    $settings = app(SettingsService::class);

    expect($settings->integer('game.point_value_minor'))->toBe(1_000);
    expect($settings->money('game.point_value_minor')->toDecimalString())->toBe('10.00');

    $admin = userWithRole(RoleSlug::SuperAdministrator);
    $settings->set('game.point_value_minor', 2_500, $admin, 'Test change');

    expect($settings->integer('game.point_value_minor'))->toBe(2_500);
    expect(SettingChange::query()->where('setting_key', 'game.point_value_minor')->count())->toBe(1);
    expect(SettingChange::query()->first()->old_value)->toBe(['value' => 1_000]);
});

test('real-money feature flags are disabled by default and fail closed', function () {
    $flags = app(FeatureFlagService::class);

    expect($flags->enabled(FeatureFlagService::REAL_MONEY_DEPOSITS))->toBeFalse();
    expect($flags->enabled(FeatureFlagService::PLATFORM_FEES))->toBeFalse();
    expect($flags->enabled('never_defined'))->toBeFalse();

    $flags->setEnabled(FeatureFlagService::PLATFORM_FEES, true);

    expect($flags->enabled(FeatureFlagService::PLATFORM_FEES))->toBeTrue();
});

test('settings and feature flags work with a serializing cache store', function () {
    // The Redis/file cache serializes values and Laravel 13 refuses to
    // unserialize objects (cache.serializable_classes = false). Regression
    // test for models being cached instead of scalars/arrays.
    config([
        'cache.stores.serializing' => ['driver' => 'array', 'serialize' => true],
        'cache.default' => 'serializing',
        'cache.serializable_classes' => false,
    ]);

    Cache::purge('serializing');
    Cache::store('serializing')->flush();

    $settings = app(SettingsService::class);
    $flags = app(FeatureFlagService::class);

    expect($settings->integer('game.point_value_minor'))->toBe(1_000)
        ->and($flags->enabled(FeatureFlagService::PLATFORM_FEES))->toBeFalse();

    $flags->setEnabled(FeatureFlagService::PLATFORM_FEES, true);

    expect($flags->enabled(FeatureFlagService::PLATFORM_FEES))->toBeTrue();
    expect(Cache::get('system_setting:v2:game.point_value_minor'))
        ->toBe(['exists' => true, 'value' => 1_000]);
});

test('a suspended account is treated as not playable', function () {
    $user = User::factory()->create([
        'username' => 'suspended-user',
        'account_status' => 'suspended',
    ]);

    expect($user->isSuspended())->toBeTrue();
});
