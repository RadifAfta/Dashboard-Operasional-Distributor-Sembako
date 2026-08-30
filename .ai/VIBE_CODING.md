# 🚀 Vibe Coding Master Guidelines

Dokumen ini adalah **panduan standar operasional untuk AI Coding Assistant (Antigravity, Cursor, Claude Code, GitHub Copilot)** saat menambahkan fitur baru, halaman admin, atau modul bisnis di atas repositori **Dashboard-Admin-Boilerplate**.

---

## 1. 🏗️ Tech Stack & Arsitektur Inti

- **Backend**: Laravel 13 (PHP 8.3), Spatie Laravel Permission, Laravel Pint (PSR-12).
- **Frontend**: Vue 3 (Composition API `<script setup>`), Inertia.js v2, Tailwind CSS v4.
- **Icons**: `lucide-vue-next`.
- **Database**: SQLite (Development) / PostgreSQL / MySQL (Production).

---

## 2. 🎨 Aturan Pewarnaan Wajib (*Brand Theming*)

> [!IMPORTANT]
> **JANGAN PERNAH** melakukan hardcode warna aksen spesifik (seperti `indigo-600`, `blue-600`, atau `purple-600`) pada tombol utama, active link, badge status, atau focus rings!
> Template ini memiliki fitur **Dynamic Brand Theming** yang memungkinkan klien mengubah warna aksen perusahaan via CSS Variables.

Gunakan selalu utility class **`brand`**:
- **Tombol Utama (*Primary Button*)**: `bg-brand hover:opacity-90 text-white shadow-sm shadow-brand/20`
- **Tautan / Aksen Teks Aktif**: `text-brand hover:underline`
- **Latar Belakang Halus (*Subtle Highlight*)**: `bg-brand/10 text-brand border border-brand/20`
- **Indikator / Node Aktif**: `bg-brand` atau `stroke-brand`
- **Border Focus Formulir**: `focus:ring-2 focus:ring-brand/20 focus:border-brand`
- **Checkbox Aktif**: `text-brand border-slate-300 rounded focus:ring-brand`

---

## 3. ⚡ Cara Cepat Membuat Modul Baru: Artisan Generator

Saat Anda diminta membuat modul bisnis (misalnya: *Produk, Pesanan, Pelanggan, Inventaris*), **prioritaskan menggunakan command generator berikut**:

```bash
php artisan make:admin-crud Product --icon="Package" --group="Master Data"
```

Perintah ini otomatis meng-generate:
1. Migration database lengkap dengan schema dasar.
2. Model Eloquent dengan `$fillable` dan search scope.
3. FormRequest validasi ketat.
4. Controller berbasis Inertia dengan search, sort, pagination, bulk delete, dan Spatie Permissions.
5. Halaman Vue `Index.vue` yang sudah terintegrasi dengan `<DataTable>`, modal create/edit, dan `<ConfirmationModal>`.
6. Otomatis mendaftarkan route dan menu navigasi di `config/admin_menu.php`.

Setelah digenerate, AI tinggal menyesuaikan kolom spesifik bisnis klien (misal menambah kolom `harga`, `sku`, dll).

---

## 4. 🧩 Standar Komponen UI Wajib

### A. Membungkus Halaman Admin
Setiap halaman admin di `resources/js/Pages/Admin/` **wajib** menggunakan `<AdminLayout>`:

```vue
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AdminLayout title="Nama Halaman">
        <Head title="Nama Halaman" />
        <!-- Konten Halaman -->
    </AdminLayout>
</template>
```

### B. Menampilkan Data Tabel (`<DataTable>`)
Gunakan selalu komponen `<DataTable>` yang sudah memiliki fitur bawaan: pencarian instan, sorting kolom, toggle kerapatan baris (comfortable vs compact), column visibility, export CSV, dan bulk actions.

```vue
<script setup>
import DataTable from '@/Components/Admin/DataTable.vue';

const columns = [
    { key: 'code', label: 'Kode', sortable: true },
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'created_at', label: 'Dibuat', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false, align: 'right' },
];
</script>

<template>
    <DataTable
        :rows="items.data"
        :columns="columns"
        :pagination="items"
        :selectable="true"
        search-placeholder="Cari data..."
        export-file-name="daftar_data"
    >
        <!-- Slot Tombol Aksi Header -->
        <template #actions>
            <button class="px-3 py-1.5 text-xs font-bold text-white bg-brand hover:opacity-90 rounded-lg">
                + Tambah Data
            </button>
        </template>

        <!-- Slot Custom Cell -->
        <template #cell(status)="{ value }">
            <Badge :variant="value === 'active' ? 'success' : 'neutral'">
                {{ value }}
            </Badge>
        </template>

        <!-- Slot Aksi Baris -->
        <template #rowActions="{ row }">
            <button @click="edit(row)" class="p-1.5 text-slate-400 hover:text-brand">Edit</button>
            <button @click="destroy(row)" class="p-1.5 text-slate-400 hover:text-rose-600">Hapus</button>
        </template>
    </DataTable>
</template>
```

### C. Notifikasi Mengambang (`useToast`)
Jangan pernah menggunakan `alert()` browser. Gunakan composable `useToast`:

```javascript
import { useToast } from '@/Composables/useToast';

const toast = useToast();

// Notifikasi Sukses
toast.success('Berhasil', 'Data produk telah disimpan.');

// Notifikasi Error
toast.error('Gagal', 'Periksa kembali formulir yang diisi.');

// Notifikasi Peringatan
toast.warning('Peringatan', 'Stok produk hampir habis.');

// Notifikasi Info
toast.info('Info', 'Proses sinkronisasi sedang berjalan.');
```

### D. Flash Message dari Controller Laravel
Di Controller Laravel, kirim notifikasi via session flash:

```php
return redirect()->route('admin.products.index')
    ->with('success', 'Produk baru berhasil ditambahkan.');
```

Inertia dan `AdminLayout.vue` akan otomatis menampilkan popup Toast sukses tanpa kode tambahan di Vue!

---

## 5. 📂 Struktur Folder Proyek

```text
app/
├── Http/
│   ├── Controllers/Admin/       # Controller khusus dashboard admin
│   └── Requests/Admin/          # Form request validasi
├── Models/                      # Model Eloquent
config/
└── admin_menu.php               # Konfigurasi menu navigasi sidebar & breadcrumbs
resources/js/
├── Components/Admin/            # Komponen re-usable (DataTable, Badge, Alert, dll)
├── Composables/                 # useToast.js, dll
├── Layouts/                     # AdminLayout.vue (Framed App Shell)
├── Pages/Admin/                 # Halaman-halaman admin
└── Utils/                       # brandTheme.js, errorHandler.js
```

---

## 6. 🛡️ Pola Controller Admin Standar

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy($request->sort_field ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }
}
```

---

## 7. 🧪 Checklist Sebelum Finalisasi Pekerjaan

Sebelum menyelesaikan tugas coding bersama user:
1. Jalankan `npm run build` untuk memverifikasi tidak ada error sintaks Vue atau Vite.
2. Jalankan `php artisan test --compact` untuk memastikan seluruh unit/feature tests lulus.
3. Jalankan `vendor/bin/pint --format agent` untuk memformat kode PHP agar rapi sesuai standar Laravel.
