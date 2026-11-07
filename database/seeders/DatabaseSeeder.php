<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\PlayerProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            FeatureFlagSeeder::class,
            RoomSeeder::class,
            PaymentMethodSeeder::class,
        ]);

        // Exactly four players, enough to fill one Traditional 13-card Chinese Poker table.
        // player1 also holds the super administrator role so the admin dashboard can be tested.
        // Development credentials only: password is "password" for every account.
        $users = [
            [
                'username' => 'player1',
                'name' => 'Player One',
                'email' => 'player1@example.com',
                'password' => 'password',
                'role' => 'player',
                'super_admin' => true,
            ],
            [
                'username' => 'player2',
                'name' => 'Player Two',
                'email' => 'player2@example.com',
                'password' => 'password',
                'role' => 'player',
                'super_admin' => false,
            ],
            [
                'username' => 'player3',
                'name' => 'Player Three',
                'email' => 'player3@example.com',
                'password' => 'password',
                'role' => 'player',
                'super_admin' => false,
            ],
            [
                'username' => 'player4',
                'name' => 'Player Four',
                'email' => 'player4@example.com',
                'password' => 'password',
                'role' => 'player',
                'super_admin' => false,
            ],
        ];

        // Development only: remove any account that is not one of the four test players so the
        // database only ever contains the players used to fill a single table.
        $usernames = collect($users)->pluck('username')->all();
        User::query()->whereNotIn('username', $usernames)->get()->each->delete();

        $roles = Role::query()->pluck('id', 'slug');

        foreach ($users as $index => $userData) {
            $isSuperAdmin = (bool) $userData['super_admin'];
            unset($userData['super_admin']);

            // updateOrCreate keeps the same seeded users in place if you run the seeder again.
            $user = User::query()->updateOrCreate(
                ['username' => $userData['username']],
                $userData,
            );

            $roleSlugs = $isSuperAdmin
                ? [RoleSlug::SuperAdministrator->value, RoleSlug::Player->value]
                : [RoleSlug::Player->value];

            $user->roles()->syncWithoutDetaching(
                collect($roleSlugs)->map(fn (string $slug) => $roles[$slug])->all()
            );

            PlayerProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $userData['name'],
                    'country_code' => 'PH',
                    'timezone' => 'Asia/Manila',
                    'date_of_birth' => now()->subYears(25 + $index)->toDateString(),
                ],
            );

            // Development credits only: clearly labelled test credit, never real money.
            Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'currency' => 'PHP',
                    'available_minor' => 100_000,
                    'is_test_credit' => true,
                ],
            );
        }
    }
}
