<?php

namespace Database\Seeders;

use App\Models\User;
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
        // These records give us predictable users to test the CRUD screens and pagination.
        $users = [
            [
                'username' => 'admin',
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => 'admin',
                'role' => 'admin',
            ],
            [
                'username' => 'user',
                'name' => 'User',
                'email' => 'user@example.com',
                'password' => 'user',
                'role' => 'user',
            ],
            [
                'username' => 'demo1',
                'name' => 'Demo One',
                'email' => 'demo1@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo2',
                'name' => 'Demo Two',
                'email' => 'demo2@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo3',
                'name' => 'Demo Three',
                'email' => 'demo3@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo4',
                'name' => 'Demo Four',
                'email' => 'demo4@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo5',
                'name' => 'Demo Five',
                'email' => 'demo5@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo6',
                'name' => 'Demo Six',
                'email' => 'demo6@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo7',
                'name' => 'Demo Seven',
                'email' => 'demo7@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo8',
                'name' => 'Demo Eight',
                'email' => 'demo8@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo9',
                'name' => 'Demo Nine',
                'email' => 'demo9@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo10',
                'name' => 'Demo Ten',
                'email' => 'demo10@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo11',
                'name' => 'Demo Eleven',
                'email' => 'demo11@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
            [
                'username' => 'demo12',
                'name' => 'Demo Twelve',
                'email' => 'demo12@example.com',
                'password' => 'demo123',
                'role' => 'user',
            ],
        ];

        foreach ($users as $userData) {
            // updateOrCreate keeps the same seeded users in place if you run the seeder again.
            User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );
        }
    }
}
