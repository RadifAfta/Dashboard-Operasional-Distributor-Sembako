<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Navigation Menu Configuration - Toko Grosir & Distributor Sembako
    |--------------------------------------------------------------------------
    */

    'items' => [
        // 1. UTAMA / WORKSPACE
        [
            'section' => 'Utama',
            'title' => 'Dashboard',
            'icon' => 'LayoutDashboard',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard*',
            'permission' => null,
        ],

        // 2. TRANSAKSI & OPERASIONAL HARIAN
        [
            'section' => 'Transaksi',
            'title' => 'Kasir (POS)',
            'icon' => 'ShoppingCart',
            'route' => 'admin.pos.index',
            'active' => 'admin.pos.*',
            'permission' => null,
            'badge' => 'Cepat',
        ],
        [
            'section' => 'Transaksi',
            'title' => 'Piutang & Jatuh Tempo',
            'icon' => 'Clock',
            'route' => 'admin.debts.index',
            'active' => 'admin.debts.*',
            'permission' => null,
        ],
        [
            'section' => 'Transaksi',
            'title' => 'Rekap Kas & Tutup Buku',
            'icon' => 'FileText',
            'route' => 'admin.closure.index',
            'active' => 'admin.closure.*',
            'permission' => null,
        ],

        // 3. MASTER DATA
        [
            'section' => 'Data Master',
            'title' => 'Master Data',
            'icon' => 'Package',
            'active' => 'admin.products.*|admin.customers.*',
            'permission' => null,
            'children' => [
                [
                    'title' => 'Produk & Multi-Satuan',
                    'route' => 'admin.products.index',
                    'active' => 'admin.products.*',
                    'permission' => null,
                ],
                [
                    'title' => 'Pelanggan Grosir',
                    'route' => 'admin.customers.index',
                    'active' => 'admin.customers.*',
                    'permission' => null,
                ],
            ],
        ],

        // 4. PENGATURAN & SISTEM
        [
            'section' => 'Sistem & Pengaturan',
            'title' => 'Manajemen User',
            'icon' => 'Users',
            'active' => 'admin.users.*|admin.roles.*',
            'permission' => 'view-users',
            'children' => [
                [
                    'title' => 'Semua Pengguna',
                    'route' => 'admin.users.index',
                    'active' => 'admin.users.index',
                    'permission' => 'view-users',
                ],
                [
                    'title' => 'Role & Izin Akses',
                    'route' => 'admin.roles.index',
                    'active' => 'admin.roles.*',
                    'permission' => 'view-roles',
                ],
            ],
        ],
        [
            'section' => 'Sistem & Pengaturan',
            'title' => 'Pengaturan Toko',
            'icon' => 'Settings',
            'route' => 'admin.settings.index',
            'active' => 'admin.settings.*',
            'permission' => 'view-settings',
        ],
        [
            'section' => 'Sistem & Pengaturan',
            'title' => 'Audit Log',
            'icon' => 'Activity',
            'route' => 'admin.audit-logs.index',
            'active' => 'admin.audit-logs.*',
            'permission' => 'view-audit-logs',
        ],
    ],
];
