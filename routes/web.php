<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClosureController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // 1. Dashboard Operasional
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Master Data Barang & Multi-Satuan
    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::post('products/batch-import', [ProductController::class, 'batchImport'])->name('products.batch-import');
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);

    // Pelanggan Grosir / Mitra Warung
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);

    // 3. Transaksi Kasir (POS)
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

    // 4. Piutang & Jatuh Tempo
    Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('debts/{transaction}/settle', [DebtController::class, 'settle'])->name('debts.settle');

    // 5. Laporan & Tutup Buku (Rekap Kas Fisik & WA Gateway)
    Route::get('closure', [ClosureController::class, 'index'])->name('closure.index');
    Route::post('closure', [ClosureController::class, 'store'])->name('closure.store');
    Route::post('closure/whatsapp', [ClosureController::class, 'triggerWhatsApp'])->name('closure.whatsapp');
    Route::get('closure/export', [ClosureController::class, 'exportExcel'])->name('closure.export');

    // Users Management
    Route::post('users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-delete');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

    // Roles & Permissions
    Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);

    // System Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
