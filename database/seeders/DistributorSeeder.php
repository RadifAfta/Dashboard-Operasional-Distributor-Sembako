<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DistributorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Ensure App Settings reflect the Distributor Toko Sembako
        Setting::set('app_name', 'Distributor Sembako Berkah Mandiri');
        Setting::set('app_description', 'Pusat Grosir & Distribusi Sembako Terpadu');
        Setting::set('currency_symbol', 'Rp');
        Setting::set('brand_color', 'emerald');
        Setting::set('store_address', 'Jl. Pasar Induk Kramat Jati Blok C No. 12, Jakarta Timur');
        Setting::set('store_phone', '0812-8888-9999');
        Setting::set('demo_wa_number', '6281288889999');

        $adminUser = User::first() ?? User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // 1. Seed Customers (Toko Mitra / Pelanggan Grosir)
        $customersData = [
            [
                'code' => 'CUST-001',
                'name' => 'Toko Berkah Jaya (Warung Madura)',
                'phone' => '081234567890',
                'address' => 'Jl. Raya Condet No. 45, Jakarta Timur',
                'credit_limit' => 15000000,
                'status' => 'active',
            ],
            [
                'code' => 'CUST-002',
                'name' => 'Toko Sumber Rejeki (Pasar Baru)',
                'phone' => '081398765432',
                'address' => 'Komp. Ruko Pasar Baru Blok B-3, Jakarta Pusat',
                'credit_limit' => 25000000,
                'status' => 'active',
            ],
            [
                'code' => 'CUST-003',
                'name' => 'Warung Sembako Bu Siti',
                'phone' => '085712345678',
                'address' => 'Jl. Kebon Pala No. 18, Jakarta Timur',
                'credit_limit' => 8000000,
                'status' => 'active',
            ],
            [
                'code' => 'CUST-004',
                'name' => 'RM Padang Salero Kito',
                'phone' => '082155667788',
                'address' => 'Jl. Pemuda No. 88, Rawamangun',
                'credit_limit' => 20000000,
                'status' => 'active',
            ],
            [
                'code' => 'CUST-005',
                'name' => 'Toko Subur Makmur',
                'phone' => '087811223344',
                'address' => 'Jl. Pahlawan Revolusi No. 102, Pondok Bambu',
                'credit_limit' => 12000000,
                'status' => 'active',
            ],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[] = Customer::updateOrCreate(['code' => $c['code']], $c);
        }

        // 2. Seed Master Data Barang & Multi-Satuan
        $productsData = [
            [
                'sku' => 'SKU-MNY-001',
                'barcode' => '8992753110011',
                'name' => 'Minyak Goreng Bimoli Klasik 2L',
                'category' => 'Minyak Goreng',
                'cost_price' => 33000,
                'selling_price' => 36500,
                'base_unit' => 'Pouch',
                'min_stock' => 24,
                'current_stock' => 180, // 30 Dus
                'status' => 'active',
                'description' => 'Minyak goreng kelapa sawit murni kemasan pouch 2 Liter',
                'units' => [
                    [
                        'unit_name' => 'Dus (6 Pouch)',
                        'conversion_ratio' => 6,
                        'selling_price' => 215000,
                        'barcode' => '8992753110012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-MNY-002',
                'barcode' => '8992753110028',
                'name' => 'Minyak Goreng Rose Brand 1L',
                'category' => 'Minyak Goreng',
                'cost_price' => 16200,
                'selling_price' => 18000,
                'base_unit' => 'Pouch',
                'min_stock' => 36,
                'current_stock' => 240, // 20 Dus
                'status' => 'active',
                'description' => 'Minyak goreng kemasan ekonomis 1 Liter',
                'units' => [
                    [
                        'unit_name' => 'Dus (12 Pouch)',
                        'conversion_ratio' => 12,
                        'selling_price' => 212000,
                        'barcode' => '8992753110029',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-BRS-001',
                'barcode' => '8991234000010',
                'name' => 'Beras Rojolele Super Premium',
                'category' => 'Beras & Padi',
                'cost_price' => 13500,
                'selling_price' => 15000,
                'base_unit' => 'Kg',
                'min_stock' => 100,
                'current_stock' => 1250,
                'status' => 'active',
                'description' => 'Beras pulen harum kualitas istimewa tanpa pemutih',
                'units' => [
                    [
                        'unit_name' => 'Sak (25 Kg)',
                        'conversion_ratio' => 25,
                        'selling_price' => 365000,
                        'barcode' => '8991234000011',
                    ],
                    [
                        'unit_name' => 'Sak (50 Kg)',
                        'conversion_ratio' => 50,
                        'selling_price' => 720000,
                        'barcode' => '8991234000012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-BRS-002',
                'barcode' => '8991234000020',
                'name' => 'Beras Pandan Wangi Cianjur',
                'category' => 'Beras & Padi',
                'cost_price' => 14800,
                'selling_price' => 16500,
                'base_unit' => 'Kg',
                'min_stock' => 100,
                'current_stock' => 750,
                'status' => 'active',
                'description' => 'Beras aromatik asli Cianjur',
                'units' => [
                    [
                        'unit_name' => 'Sak (25 Kg)',
                        'conversion_ratio' => 25,
                        'selling_price' => 405000,
                        'barcode' => '8991234000021',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-GLA-001',
                'barcode' => '8993456000015',
                'name' => 'Gula Pasir Gulaku Tebu Kuning 1Kg',
                'category' => 'Gula Pasir',
                'cost_price' => 16000,
                'selling_price' => 17500,
                'base_unit' => 'Kg',
                'min_stock' => 50,
                'current_stock' => 600,
                'status' => 'active',
                'description' => 'Gula tebu murni kemasan 1 kg',
                'units' => [
                    [
                        'unit_name' => 'Karung/Dus (20 Kg)',
                        'conversion_ratio' => 20,
                        'selling_price' => 345000,
                        'barcode' => '8993456000016',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-GLA-002',
                'barcode' => '8993456000022',
                'name' => 'Gula Pasir Kristal Putih GMP',
                'category' => 'Gula Pasir',
                'cost_price' => 15500,
                'selling_price' => 17000,
                'base_unit' => 'Kg',
                'min_stock' => 100,
                'current_stock' => 1000,
                'status' => 'active',
                'description' => 'Gula pasir kristal putih Gunung Madu',
                'units' => [
                    [
                        'unit_name' => 'Sak (50 Kg)',
                        'conversion_ratio' => 50,
                        'selling_price' => 835000,
                        'barcode' => '8993456000023',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-TPG-001',
                'barcode' => '8997890000011',
                'name' => 'Tepung Terigu Segitiga Biru 1Kg',
                'category' => 'Tepung & Gandum',
                'cost_price' => 11000,
                'selling_price' => 12500,
                'base_unit' => 'Kg',
                'min_stock' => 50,
                'current_stock' => 500,
                'status' => 'active',
                'description' => 'Tepung serbaguna protein sedang Bogasari',
                'units' => [
                    [
                        'unit_name' => 'Sak (25 Kg)',
                        'conversion_ratio' => 25,
                        'selling_price' => 305000,
                        'barcode' => '8997890000012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-MIE-001',
                'barcode' => '8998866200011',
                'name' => 'Indomie Goreng Spesial 85g',
                'category' => 'Mie Instan',
                'cost_price' => 2850,
                'selling_price' => 3200,
                'base_unit' => 'Bungkus',
                'min_stock' => 120,
                'current_stock' => 1600, // 40 Dus
                'status' => 'active',
                'description' => 'Mie instan goreng nomor 1 di Indonesia',
                'units' => [
                    [
                        'unit_name' => 'Karton/Dus (40 Bungkus)',
                        'conversion_ratio' => 40,
                        'selling_price' => 124000,
                        'barcode' => '8998866200012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-MIE-002',
                'barcode' => '8998866200028',
                'name' => 'Indomie Kuah Soto Mie 70g',
                'category' => 'Mie Instan',
                'cost_price' => 2700,
                'selling_price' => 3000,
                'base_unit' => 'Bungkus',
                'min_stock' => 120,
                'current_stock' => 1000,
                'status' => 'active',
                'description' => 'Mie instan kuah rasa soto mie',
                'units' => [
                    [
                        'unit_name' => 'Karton/Dus (40 Bungkus)',
                        'conversion_ratio' => 40,
                        'selling_price' => 116000,
                        'barcode' => '8998866200029',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-SSU-001',
                'barcode' => '8992759001001',
                'name' => 'Susu Kental Manis Frisian Flag Cokelat',
                'category' => 'Susu & Olahan',
                'cost_price' => 10800,
                'selling_price' => 12500,
                'base_unit' => 'Kaleng',
                'min_stock' => 48,
                'current_stock' => 288,
                'status' => 'active',
                'description' => 'Susu bendera kaleng 370g',
                'units' => [
                    [
                        'unit_name' => 'Dus (48 Kaleng)',
                        'conversion_ratio' => 48,
                        'selling_price' => 580000,
                        'barcode' => '8992759001002',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-TLR-001',
                'barcode' => '8990000999011',
                'name' => 'Telur Ayam Ras Fresh Peternak',
                'category' => 'Telur & Unggas',
                'cost_price' => 25500,
                'selling_price' => 28000,
                'base_unit' => 'Kg',
                'min_stock' => 30,
                'current_stock' => 180,
                'status' => 'active',
                'description' => 'Telur ayam negeri segar per kg',
                'units' => [
                    [
                        'unit_name' => 'Peti/Tray (15 Kg)',
                        'conversion_ratio' => 15,
                        'selling_price' => 410000,
                        'barcode' => '8990000999012',
                    ],
                ],
            ],

            // ⚠️ 5 Barang Kritis untuk Tabel Quick Alert (< min_stock)
            [
                'sku' => 'SKU-KCP-001',
                'barcode' => '8999999123011',
                'name' => 'Kecap Manis Bango 520ml',
                'category' => 'Bumbu & Penyedap',
                'cost_price' => 21000,
                'selling_price' => 24500,
                'base_unit' => 'Pouch',
                'min_stock' => 30,
                'current_stock' => 3, // Kritis! (3 < 30)
                'status' => 'active',
                'description' => 'Kecap manis kedelai hitam Malika',
                'units' => [
                    [
                        'unit_name' => 'Dus (12 Pouch)',
                        'conversion_ratio' => 12,
                        'selling_price' => 285000,
                        'barcode' => '8999999123012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-MNY-003',
                'barcode' => '8992753330011',
                'name' => 'Minyak Goreng Tropical Botol 2L',
                'category' => 'Minyak Goreng',
                'cost_price' => 35000,
                'selling_price' => 38500,
                'base_unit' => 'Botol',
                'min_stock' => 24,
                'current_stock' => 4, // Kritis! (4 < 24)
                'status' => 'active',
                'description' => 'Minyak 2x penyaringan kemasan botol',
                'units' => [
                    [
                        'unit_name' => 'Dus (6 Botol)',
                        'conversion_ratio' => 6,
                        'selling_price' => 226000,
                        'barcode' => '8992753330012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-SBN-001',
                'barcode' => '8999999456011',
                'name' => 'Sabun Cuci Piring Sunlight Jeruk Nipis 650ml',
                'category' => 'Rumah Tangga',
                'cost_price' => 13500,
                'selling_price' => 15500,
                'base_unit' => 'Pouch',
                'min_stock' => 24,
                'current_stock' => 5, // Kritis! (5 < 24)
                'status' => 'active',
                'description' => 'Pembersih lemak kemasan pouch refill',
                'units' => [
                    [
                        'unit_name' => 'Dus (12 Pouch)',
                        'conversion_ratio' => 12,
                        'selling_price' => 180000,
                        'barcode' => '8999999456012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-MIE-003',
                'barcode' => '8998866770011',
                'name' => 'Mie Sedaap Goreng Ayam Krispi 88g',
                'category' => 'Mie Instan',
                'cost_price' => 2800,
                'selling_price' => 3150,
                'base_unit' => 'Bungkus',
                'min_stock' => 80,
                'current_stock' => 8, // Kritis! (8 < 80)
                'status' => 'active',
                'description' => 'Mie instan taburan kriuk renyah',
                'units' => [
                    [
                        'unit_name' => 'Karton/Dus (40 Bungkus)',
                        'conversion_ratio' => 40,
                        'selling_price' => 122000,
                        'barcode' => '8998866770012',
                    ],
                ],
            ],
            [
                'sku' => 'SKU-BMB-002',
                'barcode' => '8999999888011',
                'name' => 'Royco Bumbu Pelezat Rasa Ayam 230g',
                'category' => 'Bumbu & Penyedap',
                'cost_price' => 9500,
                'selling_price' => 11000,
                'base_unit' => 'Pouch',
                'min_stock' => 25,
                'current_stock' => 6, // Kritis! (6 < 25)
                'status' => 'active',
                'description' => 'Bumbu kaldu pelezat serbaguna',
                'units' => [
                    [
                        'unit_name' => 'Pak (12 Pouch)',
                        'conversion_ratio' => 12,
                        'selling_price' => 128000,
                        'barcode' => '8999999888012',
                    ],
                ],
            ],
        ];

        $savedProducts = [];
        foreach ($productsData as $pData) {
            $units = $pData['units'] ?? [];
            unset($pData['units']);

            $product = Product::updateOrCreate(['sku' => $pData['sku']], $pData);

            ProductUnit::where('product_id', $product->id)->delete();
            foreach ($units as $u) {
                $product->units()->create($u);
            }

            $savedProducts[] = $product;
        }

        // 3. Seed Realistic Transactions (7-day distribution of Cash & Tempo + Overdue)
        TransactionItem::query()->delete();
        DebtPayment::query()->delete();
        Transaction::query()->delete();

        // 3A. Overdue Tempo Transactions (Transaski Tempo Masa Lalu yang Belum Lunas)
        // Customer 1: Toko Berkah Jaya
        $tOverdue1 = Transaction::create([
            'invoice_number' => 'TRX-20260815-0012',
            'customer_id' => $customers[0]->id,
            'user_id' => $adminUser->id,
            'subtotal' => 8500000,
            'discount_amount' => 100000,
            'total_amount' => 8400000,
            'cost_total' => 7400000,
            'profit_total' => 1000000,
            'payment_method' => 'credit',
            'payment_status' => 'unpaid',
            'due_date' => Carbon::now()->subDays(5)->format('Y-m-d'), // Jatuh tempo 5 hari lalu!
            'credit_days' => 14,
            'paid_amount' => 0,
            'remaining_debt' => 8400000,
            'notes' => 'Tempo 14 hari pasokan warung madura mingguan',
            'created_at' => Carbon::now()->subDays(19),
        ]);

        $tOverdue1->items()->create([
            'product_id' => $savedProducts[0]->id,
            'product_name' => $savedProducts[0]->name,
            'unit_name' => 'Dus (6 Pouch)',
            'conversion_ratio' => 6,
            'cost_price' => 33000,
            'selling_price' => 215000,
            'quantity' => 20,
            'base_quantity' => 120,
            'discount_amount' => 0,
            'subtotal' => 4300000,
            'profit' => 340000,
        ]);

        $tOverdue1->items()->create([
            'product_id' => $savedProducts[2]->id,
            'product_name' => $savedProducts[2]->name,
            'unit_name' => 'Sak (25 Kg)',
            'conversion_ratio' => 25,
            'cost_price' => 13500,
            'selling_price' => 365000,
            'quantity' => 11,
            'base_quantity' => 275,
            'discount_amount' => 100000,
            'subtotal' => 4100000,
            'profit' => 660000,
        ]);

        // Customer 3: Warung Sembako Bu Siti (Overdue parsial, cicil 1x)
        $tOverdue2 = Transaction::create([
            'invoice_number' => 'TRX-20260818-0034',
            'customer_id' => $customers[2]->id,
            'user_id' => $adminUser->id,
            'subtotal' => 5200000,
            'discount_amount' => 0,
            'total_amount' => 5200000,
            'cost_total' => 4550000,
            'profit_total' => 650000,
            'payment_method' => 'credit',
            'payment_status' => 'partial',
            'due_date' => Carbon::now()->subDays(3)->format('Y-m-d'), // Jatuh tempo 3 hari lalu!
            'credit_days' => 7,
            'paid_amount' => 2000000,
            'remaining_debt' => 3200000,
            'notes' => 'Tempo 7 hari, baru dicicil Rp 2.000.000',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $tOverdue2->debtPayments()->create([
            'customer_id' => $customers[2]->id,
            'user_id' => $adminUser->id,
            'amount' => 2000000,
            'payment_method' => 'cash',
            'notes' => 'Cicilan pertama diserahkan tunai di toko',
            'created_at' => Carbon::now()->subDays(6),
        ]);

        // Update customer debts
        $customers[0]->update(['current_debt' => 8400000]);
        $customers[2]->update(['current_debt' => 3200000]);

        // 3B. Past 6 days transactions (Day -6 to Day -1) for 7-day Bar Chart
        for ($i = 6; $i >= 1; $i--) {
            $dayDate = Carbon::now()->subDays($i);

            // Transaksi Tunai Hari itu
            $cashTurnover = rand(8, 16) * 1000000;
            $cashCost = (int) round($cashTurnover * 0.86);
            $cashProfit = $cashTurnover - $cashCost;

            Transaction::create([
                'invoice_number' => 'TRX-'.$dayDate->format('Ymd').'-C'.rand(10, 99),
                'customer_id' => null,
                'user_id' => $adminUser->id,
                'subtotal' => $cashTurnover,
                'discount_amount' => 0,
                'total_amount' => $cashTurnover,
                'cost_total' => $cashCost,
                'profit_total' => $cashProfit,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'cash_given' => $cashTurnover,
                'cash_change' => 0,
                'paid_amount' => $cashTurnover,
                'remaining_debt' => 0,
                'notes' => 'Penjualan tunai harian',
                'created_at' => $dayDate->copy()->setTime(11, 30),
            ]);

            // Transaksi Tempo Hari itu
            $creditTurnover = rand(6, 14) * 1000000;
            $creditCost = (int) round($creditTurnover * 0.87);
            $creditProfit = $creditTurnover - $creditCost;
            $cust = $customers[array_rand($customers)];

            Transaction::create([
                'invoice_number' => 'TRX-'.$dayDate->format('Ymd').'-T'.rand(10, 99),
                'customer_id' => $cust->id,
                'user_id' => $adminUser->id,
                'subtotal' => $creditTurnover,
                'discount_amount' => 0,
                'total_amount' => $creditTurnover,
                'cost_total' => $creditCost,
                'profit_total' => $creditProfit,
                'payment_method' => 'credit',
                'payment_status' => 'unpaid',
                'due_date' => $dayDate->copy()->addDays(14)->format('Y-m-d'),
                'credit_days' => 14,
                'paid_amount' => 0,
                'remaining_debt' => $creditTurnover,
                'notes' => 'Pengiriman sembako tempo ke '.$cust->name,
                'created_at' => $dayDate->copy()->setTime(14, 20),
            ]);
        }

        // 3C. Today's Transactions (Day 0)
        // Transaksi Tunai 1 (Eceran & Grosir Toko)
        $today1 = Transaction::create([
            'invoice_number' => 'TRX-'.now()->format('Ymd').'-0001',
            'customer_id' => null,
            'user_id' => $adminUser->id,
            'subtotal' => 6450000,
            'discount_amount' => 50000,
            'total_amount' => 6400000,
            'cost_total' => 5500000,
            'profit_total' => 900000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'cash_given' => 6500000,
            'cash_change' => 100000,
            'paid_amount' => 6400000,
            'remaining_debt' => 0,
            'notes' => 'Belanja tunai langsung kasir',
            'created_at' => now()->subHours(4),
        ]);

        $today1->items()->create([
            'product_id' => $savedProducts[0]->id,
            'product_name' => $savedProducts[0]->name,
            'unit_name' => 'Dus (6 Pouch)',
            'conversion_ratio' => 6,
            'cost_price' => 33000,
            'selling_price' => 215000,
            'quantity' => 15,
            'base_quantity' => 90,
            'discount_amount' => 50000,
            'subtotal' => 3175000,
            'profit' => 450000,
        ]);

        $today1->items()->create([
            'product_id' => $savedProducts[7]->id,
            'product_name' => $savedProducts[7]->name,
            'unit_name' => 'Karton/Dus (40 Bungkus)',
            'conversion_ratio' => 40,
            'cost_price' => 2850,
            'selling_price' => 124000,
            'quantity' => 25,
            'base_quantity' => 1000,
            'discount_amount' => 0,
            'subtotal' => 3100000,
            'profit' => 450000,
        ]);

        // Transaksi Transfer / QRIS Hari ini
        $today2 = Transaction::create([
            'invoice_number' => 'TRX-'.now()->format('Ymd').'-0002',
            'customer_id' => $customers[1]->id,
            'user_id' => $adminUser->id,
            'subtotal' => 7300000,
            'discount_amount' => 0,
            'total_amount' => 7300000,
            'cost_total' => 6350000,
            'profit_total' => 950000,
            'payment_method' => 'transfer',
            'payment_status' => 'paid',
            'bank_name' => 'BCA QRIS Dinamis',
            'reference_number' => 'QRIS-BCA-987214981',
            'paid_amount' => 7300000,
            'remaining_debt' => 0,
            'notes' => 'Pembayaran lunas via QRIS BCA Merchant',
            'created_at' => now()->subHours(2),
        ]);

        $today2->items()->create([
            'product_id' => $savedProducts[2]->id,
            'product_name' => $savedProducts[2]->name,
            'unit_name' => 'Sak (25 Kg)',
            'conversion_ratio' => 25,
            'cost_price' => 13500,
            'selling_price' => 365000,
            'quantity' => 20,
            'base_quantity' => 500,
            'discount_amount' => 0,
            'subtotal' => 7300000,
            'profit' => 950000,
        ]);

        // Transaksi Tempo Hari ini (RM Padang Salero Kito - Jatuh Tempo 14 hari lagi)
        $today3 = Transaction::create([
            'invoice_number' => 'TRX-'.now()->format('Ymd').'-0003',
            'customer_id' => $customers[3]->id,
            'user_id' => $adminUser->id,
            'subtotal' => 11150000,
            'discount_amount' => 0,
            'total_amount' => 11150000,
            'cost_total' => 9700000,
            'profit_total' => 1450000,
            'payment_method' => 'credit',
            'payment_status' => 'unpaid',
            'due_date' => now()->addDays(14)->format('Y-m-d'),
            'credit_days' => 14,
            'paid_amount' => 0,
            'remaining_debt' => 11150000,
            'notes' => 'Tempo 14 hari pasokan beras & minyak RM Padang',
            'created_at' => now()->subMinutes(45),
        ]);

        $customers[3]->update(['current_debt' => 11150000]);

        $today3->items()->create([
            'product_id' => $savedProducts[3]->id,
            'product_name' => $savedProducts[3]->name,
            'unit_name' => 'Sak (25 Kg)',
            'conversion_ratio' => 25,
            'cost_price' => 14800,
            'selling_price' => 405000,
            'quantity' => 20,
            'base_quantity' => 500,
            'discount_amount' => 0,
            'subtotal' => 8100000,
            'profit' => 1050000,
        ]);

        $today3->items()->create([
            'product_id' => $savedProducts[1]->id,
            'product_name' => $savedProducts[1]->name,
            'unit_name' => 'Dus (12 Pouch)',
            'conversion_ratio' => 12,
            'cost_price' => 16200,
            'selling_price' => 212000,
            'quantity' => 15,
            'base_quantity' => 180,
            'discount_amount' => 0,
            'subtotal' => 3180000,
            'profit' => 400000,
        ]);
    }
}
