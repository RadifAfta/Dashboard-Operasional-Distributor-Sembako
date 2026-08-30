# ⚡ AdminHub Pro — Reusable Laravel 13 Admin Dashboard Template

Template Starter Kit Dashboard Admin modern, responsif, dan modular berbasis **Laravel 13**, **Inertia.js v2**, **Vue 3**, **Tailwind CSS v3/v4**, dan **Spatie Laravel Permission**. Dirancang agar dapat dipakai berulang kali (*reusable boilerplate*) untuk berbagai jenis proyek web (E-commerce, SaaS, Internal ERP, CRM, dll).

---

## 🚀 Fitur Unggulan

- **Single Page Application (SPA) Experience**: Navigasi mulus tanpa reload halaman via Inertia.js + Vue 3.
- **Role & Permission Management (RBAC)**: Ditenagai `spatie/laravel-permission` lengkap dengan antarmuka matriks permission per modul.
- **Dynamic Navigation Sidebar**: Struktur navigasi terpusat di `config/admin_menu.php` yang otomatis menyaring menu berdasarkan hak akses pengguna aktif.
- **Universal Reusable DataTable Component**:
  - Pencarian dengan debounce otomatis
  - Pengurutan kolom (sorting ascending/descending)
  - Dropdown jumlah baris per halaman (10, 25, 50, 100)
  - Filter kustom via slot
  - Pilihan baris massal (bulk select) & hapus massal (bulk delete)
- **Dark Mode & Light Mode**: Tersimpan di `localStorage` dan tersinkronisasi otomatis dengan preferensi sistem OS pengguna.
- **System Settings Key-Value Engine**: Modul pengaturan aplikasi (Nama Web, Logo, Kontak, Timezone) yang disimpan di database dengan caching otomatis via `Setting::get()`.
- **Global Toast Notification**: Terintegrasi otomatis menangani flash message Laravel (`success`, `error`, `warning`, `info`).
- **Clean Architecture**: Pemisahan jelas antara controller, form requests, seeder, dan komponen UI.

---

## 🔑 Akun Default (Demo Seeders)

Setelah menjalankan database seeder, Anda dapat langsung login menggunakan:

| Role | Email | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@example.com` | `password` | Akses penuh ke seluruh fitur dan pengaturan |
| **Manager / Admin** | `manager@example.com` | `password` | Pengelolaan data user, role, dan pengaturan umum |

---

## 🛠️ Panduan Instalasi Cepat (Quick Start)

Jika Anda menyalin atau meng-clone repository ini ke proyek baru:

```bash
# 1. Masuk ke direktori proyek & pasang dependensi backend
composer install

# 2. Salin environment file
cp .env.example .env

# 3. Generate APP_KEY
php artisan key:generate

# 4. Atur konfigurasi database di .env (contoh MySQL):
# DB_CONNECTION=mysql
# DB_DATABASE=nama_database_anda
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Jalankan migrasi dan isi data awal (seeder)
php artisan migrate:fresh --seed

# 6. Pasang dependensi frontend & build asset
npm install
npm run build

# 7. Jalankan server lokal
composer run dev
# atau:
# php artisan serve
# npm run dev
```

---

## 📁 Panduan Pengembangan & Kustomisasi

### 1. Menambahkan Menu Baru ke Sidebar
Cukup edit file [`config/admin_menu.php`](file:///e:/Projects/Template-dashboard-admin/config/admin_menu.php):

```php
[
    'title' => 'Produk',
    'icon' => 'Package', // Nama Lucide icon
    'route' => 'admin.products.index',
    'active' => 'admin.products.*',
    'permission' => 'view-products', // Opsional, kosongkan jika bisa diakses semua admin
]
```

### 2. Menggunakan Komponen Reusable DataTable
Dalam file komponen Vue Anda:

```vue
<script setup>
import DataTable from '@/Components/Admin/DataTable.vue';

const columns = [
    { key: 'name', label: 'Nama Produk', sortable: true },
    { key: 'price', label: 'Harga', sortable: true },
];
</script>

<template>
    <DataTable
        :columns="columns"
        :pagination="products"
        :filters="filters"
        :selectable="true"
        v-model:selectedItems="selectedItems"
    >
        <template #cell(price)="{ value }">
            Rp {{ Number(value).toLocaleString('id-ID') }}
        </template>
        <template #rowActions="{ row }">
            <button @click="editProduct(row)">Edit</button>
        </template>
    </DataTable>
</template>
```

### 3. Mengambil Nilai Pengaturan Aplikasi
Di PHP (Controller / Blade / Service):
```php
use App\Models\Setting;

$siteName = Setting::get('app_name', 'Default Name');
```

---

## 🧪 Pengujian & Standar Kode

```bash
# Menjalankan pengujian otomatis PHPUnit
php artisan test

# Menjalankan linter & formatter Laravel Pint
vendor/bin/pint --format agent
```
