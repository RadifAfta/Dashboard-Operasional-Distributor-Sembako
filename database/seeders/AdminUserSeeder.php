<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        // 2. Regular Admin
        $admin = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'System Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('Admin');

        // 3. Regular Demo Users
        $demoUsers = [
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com'],
            ['name' => 'Siti Rahmawati', 'email' => 'siti@example.com'],
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad@example.com'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@example.com'],
            ['name' => 'Rian Pratama', 'email' => 'rian@example.com'],
            ['name' => 'Eka Putri', 'email' => 'eka@example.com'],
            ['name' => 'Fajar Nugraha', 'email' => 'fajar@example.com'],
            ['name' => 'Gita Gutawa', 'email' => 'gita@example.com'],
            ['name' => 'Hendra Setiawan', 'email' => 'hendra@example.com'],
            ['name' => 'Indah Permata', 'email' => 'indah@example.com'],
            ['name' => 'Joko Widodo', 'email' => 'joko@example.com'],
            ['name' => 'Kartika Sari', 'email' => 'kartika@example.com'],
        ];

        foreach ($demoUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('User');
        }
    }
}
