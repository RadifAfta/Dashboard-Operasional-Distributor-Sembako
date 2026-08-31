<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Customers Table (Pelanggan Grosir / Mitra Warung)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('current_debt', 15, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 2. Products Table (Master Data Barang)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('name');
            $table->string('category')->default('Umum');
            $table->decimal('cost_price', 15, 2)->default(0); // HPP Satuan Dasar
            $table->decimal('selling_price', 15, 2)->default(0); // Harga Jual Satuan Dasar
            $table->string('base_unit')->default('Pcs'); // Satuan Dasar: Pcs, Kg, Pouch, Bungkus, Liter
            $table->decimal('min_stock', 10, 2)->default(5);
            $table->decimal('current_stock', 10, 2)->default(0); // Selalu dalam satuan dasar
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Product Multi-Units (Multi-Satuan Grosir: Dus, Sak, Bal, Karton)
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('unit_name'); // e.g. Dus, Sak, Bal, Karton, Renceng
            $table->decimal('conversion_ratio', 10, 2); // 1 Dus = 12 Pouch => ratio 12
            $table->decimal('selling_price', 15, 2); // Harga satuan grosir ini
            $table->string('barcode')->nullable()->index();
            $table->timestamps();
        });

        // 4. Transactions Table (Transaksi Penjualan Kasir)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('cost_total', 15, 2)->default(0); // Total HPP untuk hitung laba kotor
            $table->decimal('profit_total', 15, 2)->default(0); // total_amount - cost_total
            $table->enum('payment_method', ['cash', 'transfer', 'credit'])->default('cash'); // cash, transfer/qris, tempo
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('paid');
            $table->decimal('cash_given', 15, 2)->nullable();
            $table->decimal('cash_change', 15, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('reference_number')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('credit_days')->nullable(); // 7, 14, 30
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_debt', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Transaction Items (Item Penjualan)
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->string('product_name');
            $table->string('unit_name'); // Satuan transaksi: Dus / Pouch
            $table->decimal('conversion_ratio', 10, 2)->default(1);
            $table->decimal('cost_price', 15, 2)->default(0); // HPP satuan dasar saat transaksi
            $table->decimal('selling_price', 15, 2)->default(0); // Harga per unit saat transaksi
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('base_quantity', 10, 2)->default(1); // quantity * conversion_ratio (potong stok dasar)
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->timestamps();
        });

        // 6. Debt Payments (Riwayat Pembayaran Cicilan / Pelunasan Piutang)
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Daily Cash Closures (Laporan Tutup Buku & Rekap Kas Fisik Laci)
        Schema::create('daily_cash_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('closure_date')->unique();
            $table->decimal('total_omzet', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('total_cash_sales', 15, 2)->default(0);
            $table->decimal('total_credit_sales', 15, 2)->default(0);
            $table->decimal('expected_cash', 15, 2)->default(0); // Total kas sistem hari ini
            $table->decimal('actual_cash', 15, 2)->default(0); // Total hitungan fisik lembaran di laci
            $table->decimal('difference', 15, 2)->default(0); // actual_cash - expected_cash
            $table->json('denominations')->nullable(); // JSON pecahan 100k, 50k, dst.
            $table->text('notes')->nullable();
            $table->string('wa_phone')->nullable();
            $table->timestamp('wa_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_cash_closures');
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
    }
};
