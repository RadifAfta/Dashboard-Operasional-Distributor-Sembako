<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the Distributor Toko Sembako operations dashboard.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today();

        // 1. Four Core Metric Cards
        // Omzet Hari Ini (Rp) - Seluruh transaksi selesai / tercatat hari ini
        $omzetToday = (float) Transaction::whereDate('created_at', $today)->sum('total_amount');

        // Estimasi Laba Kotor Hari Ini (Rp) - Revenue dikurangi HPP
        $profitToday = (float) Transaction::whereDate('created_at', $today)->sum('profit_total');

        // Total Piutang Jatuh Tempo (Rp) - Transaksi tempo yang belum lunas dan due_date < today
        $overdueDebtsQuery = Transaction::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today);

        $overdueDebtTotal = (float) $overdueDebtsQuery->sum('remaining_debt');
        $overdueDebtCount = $overdueDebtsQuery->count();

        // Jumlah Barang Kritis (Stok <= Stok Minimum)
        $criticalProductsQuery = Product::where('status', 'active')
            ->whereColumn('current_stock', '<=', 'min_stock');

        $criticalStockCount = $criticalProductsQuery->count();

        // Omzet Kemarin untuk perbandingan persentase
        $omzetYesterday = (float) Transaction::whereDate('created_at', Carbon::yesterday())->sum('total_amount');
        $omzetGrowthPct = $omzetYesterday > 0
            ? round((($omzetToday - $omzetYesterday) / $omzetYesterday) * 100, 1)
            : 0;

        // 2. Grafik Penjualan 7 Hari: Komparasi Transaksi Tunai vs Tempo
        $chartLabels = [];
        $cashSeries = [];
        $creditSeries = [];
        $totalDailySeries = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayName = $i === 0 ? 'Hari Ini' : $date->translatedFormat('d M');
            $chartLabels[] = $dayName;

            // Tunai / Transfer (Kas Langsung)
            $cashAmount = (float) Transaction::whereDate('created_at', $date)
                ->whereIn('payment_method', ['cash', 'transfer'])
                ->sum('total_amount');

            // Tempo (Kredit / Piutang)
            $creditAmount = (float) Transaction::whereDate('created_at', $date)
                ->where('payment_method', 'credit')
                ->sum('total_amount');

            $cashSeries[] = $cashAmount;
            $creditSeries[] = $creditAmount;
            $totalDailySeries[] = $cashAmount + $creditAmount;
        }

        $salesChart = [
            'labels' => $chartLabels,
            'cash_series' => $cashSeries,
            'credit_series' => $creditSeries,
            'total_daily_series' => $totalDailySeries,
            'cash_total' => array_sum($cashSeries),
            'credit_total' => array_sum($creditSeries),
            'grand_total' => array_sum($totalDailySeries),
        ];

        // 3. Tabel Quick Alert: 5 barang dengan stok paling mendekati 0
        $quickAlertProducts = Product::with('units')
            ->where('status', 'active')
            ->orderBy('current_stock', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $stockRatio = $product->min_stock > 0
                    ? round(($product->current_stock / $product->min_stock) * 100)
                    : 0;

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category,
                    'current_stock' => (float) $product->current_stock,
                    'min_stock' => (float) $product->min_stock,
                    'base_unit' => $product->base_unit,
                    'selling_price' => (float) $product->selling_price,
                    'is_critical' => $product->current_stock <= $product->min_stock,
                    'stock_ratio' => min(100, max(0, $stockRatio)),
                    'units_count' => $product->units->count(),
                ];
            });

        // Transaksi Terbaru Hari Ini
        $recentTransactions = Transaction::with(['customer', 'cashier'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'invoice_number' => $t->invoice_number,
                'customer_name' => $t->customer ? $t->customer->name : 'Pelanggan Tunai Toko',
                'payment_method' => $t->payment_method,
                'payment_status' => $t->payment_status,
                'total_amount' => (float) $t->total_amount,
                'time_ago' => $t->created_at->diffForHumans(),
                'created_at' => $t->created_at->format('H:i'),
                'is_overdue' => $t->is_overdue,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'omzet_today' => $omzetToday,
                'omzet_growth_pct' => $omzetGrowthPct,
                'profit_today' => $profitToday,
                'profit_margin_pct' => $omzetToday > 0 ? round(($profitToday / $omzetToday) * 100, 1) : 0,
                'overdue_debt_total' => $overdueDebtTotal,
                'overdue_debt_count' => $overdueDebtCount,
                'critical_stock_count' => $criticalStockCount,
            ],
            'salesChart' => $salesChart,
            'quickAlertProducts' => $quickAlertProducts,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
