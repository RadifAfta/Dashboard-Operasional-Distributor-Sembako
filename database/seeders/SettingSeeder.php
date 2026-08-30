<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'app_name',
                'value' => 'AdminHub Pro',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Nama website atau aplikasi dashboard',
            ],
            [
                'key' => 'app_description',
                'value' => 'Template Dashboard Admin modern berbasis Laravel 13 dan Inertia.js Vue 3.',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Deskripsi singkat website untuk metadata',
            ],
            [
                'key' => 'contact_email',
                'value' => 'admin@example.com',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Email kontak utama sistem',
            ],
            [
                'key' => 'enable_registration',
                'value' => '1',
                'group' => 'security',
                'type' => 'boolean',
                'description' => 'Izinkan pendaftaran akun baru dari halaman login',
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Jakarta',
                'group' => 'localization',
                'type' => 'text',
                'description' => 'Zona waktu default aplikasi',
            ],
            [
                'key' => 'currency_symbol',
                'value' => 'Rp',
                'group' => 'localization',
                'type' => 'text',
                'description' => 'Simbol mata uang untuk data transaksi',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
