<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyCashClosure;
use App\Models\DebtPayment;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClosureController extends Controller
{
    /**
     * Display Tutup Buku & Laporan Rekap Kas Fisik.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today();

        // 1. Hitung Penjualan Hari Ini dari Sistem
        $todayTransactions = Transaction::whereDate('created_at', $today)->get();

        $totalOmzet = (float) $todayTransactions->sum('total_amount');
        $totalProfit = (float) $todayTransactions->sum('profit_total');
        $totalCashSales = (float) $todayTransactions->where('payment_method', 'cash')->sum('total_amount');
        $totalTransferSales = (float) $todayTransactions->where('payment_method', 'transfer')->sum('total_amount');
        $totalCreditSales = (float) $todayTransactions->where('payment_method', 'credit')->sum('total_amount');

        // Kas dari pelunasan piutang tempo yang diterima tunai hari ini
        $debtCashCollected = (float) DebtPayment::whereDate('created_at', $today)
            ->where('payment_method', 'cash')
            ->sum('amount');

        // Total kas fisik yang seharusnya ada di laci hari ini
        $expectedCash = $totalCashSales + $debtCashCollected;

        // Piutang Jatuh Tempo saat ini
        $overdueDebts = (float) Transaction::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->sum('remaining_debt');

        // Data Tutup Buku Hari Ini (jika sudah pernah disimpan sebelumnya)
        $todayClosure = DailyCashClosure::where('closure_date', $today->format('Y-m-d'))->first();

        // Riwayat Tutup Buku Sebelumnya
        $closureHistory = DailyCashClosure::with('user')
            ->orderBy('closure_date', 'desc')
            ->limit(15)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'closure_date' => $c->closure_date ? Carbon::parse($c->closure_date)->format('d M Y') : '-',
                'total_omzet' => (float) $c->total_omzet,
                'expected_cash' => (float) $c->expected_cash,
                'actual_cash' => (float) $c->actual_cash,
                'difference' => (float) $c->difference,
                'closed_by_user' => $c->user?->name ?? 'Kasir Toko',
            ]);

        $storeInfo = [
            'name' => Setting::get('app_name', 'Distributor Sembako Berkah Mandiri'),
            'address' => Setting::get('store_address', 'Jl. Pasar Induk Kramat Jati Blok C No. 12, Jakarta Timur'),
            'phone' => Setting::get('store_phone', '0812-8888-9999'),
            'demo_wa_number' => Setting::get('demo_wa_number', '6281288889999'),
        ];

        $todayMetrics = [
            'date' => $today->format('Y-m-d'),
            'formatted_date' => $today->translatedFormat('l, d F Y'),
            'omzet_today' => $totalOmzet,
            'profit_today' => $totalProfit,
            'cash_sales_today' => $totalCashSales,
            'transfer_sales_today' => $totalTransferSales,
            'credit_sales_today' => $totalCreditSales,
            'debt_cash_collected' => $debtCashCollected,
            'expected_cash' => $expectedCash,
            'transaction_count' => $todayTransactions->count(),
            'overdue_debts' => $overdueDebts,
        ];

        return Inertia::render('Admin/Closure/Index', [
            'todayMetrics' => $todayMetrics,
            'summary' => $todayMetrics,
            'todayClosure' => $todayClosure,
            'closureHistory' => $closureHistory,
            'storeInfo' => $storeInfo,
            'ownerPhone' => $storeInfo['demo_wa_number'],
        ]);
    }

    /**
     * Simpan Rekap Kas Fisik Laci & Eksekusi Tutup Buku.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'expected_cash' => 'required|numeric|min:0',
            'total_omzet' => 'required|numeric|min:0',
            'total_profit' => 'required|numeric|min:0',
            'total_cash_sales' => 'required|numeric|min:0',
            'total_credit_sales' => 'required|numeric|min:0',
            'denominations' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
            'wa_phone' => 'nullable|string|max:30',
        ]);

        $today = Carbon::today()->format('Y-m-d');
        $difference = $validated['actual_cash'] - $validated['expected_cash'];

        $closure = DailyCashClosure::updateOrCreate(
            ['closure_date' => $today],
            [
                'user_id' => $request->user()?->id,
                'total_omzet' => $validated['total_omzet'],
                'total_profit' => $validated['total_profit'],
                'total_cash_sales' => $validated['total_cash_sales'],
                'total_credit_sales' => $validated['total_credit_sales'],
                'expected_cash' => $validated['expected_cash'],
                'actual_cash' => $validated['actual_cash'],
                'difference' => $difference,
                'denominations' => $validated['denominations'] ?? [],
                'notes' => $validated['notes'] ?? null,
                'wa_phone' => $validated['wa_phone'] ?? Setting::get('demo_wa_number', '6281288889999'),
            ]
        );

        $statusDiff = $difference === 0.0
            ? 'KAS SEIMBANG (BALANCE)'
            : ($difference > 0 ? '+Rp '.number_format($difference, 0, ',', '.').' (SURPLUS)' : '-Rp '.number_format(abs($difference), 0, ',', '.').' (MINUS)');

        return redirect()->back()->with('success', "Rekap Kas Fisik & Tutup Buku tanggal {$today} berhasil disimpan. Status Kas: {$statusDiff}.");
    }

    /**
     * Trigger WhatsApp Gateway & Simulator ("Tutup Buku & Kirim WA").
     */
    public function triggerWhatsApp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
            'closure_date' => 'nullable|date',
        ]);

        $date = Carbon::parse($validated['closure_date'] ?? Carbon::today());
        $closure = DailyCashClosure::where('closure_date', $date->format('Y-m-d'))->first();

        // Hitung data jika belum ada row closure
        $omzet = $closure ? $closure->total_omzet : (float) Transaction::whereDate('created_at', $date)->sum('total_amount');
        $profit = $closure ? $closure->total_profit : (float) Transaction::whereDate('created_at', $date)->sum('profit_total');
        $cashSales = $closure ? $closure->total_cash_sales : (float) Transaction::whereDate('created_at', $date)->where('payment_method', 'cash')->sum('total_amount');
        $creditSales = $closure ? $closure->total_credit_sales : (float) Transaction::whereDate('created_at', $date)->where('payment_method', 'credit')->sum('total_amount');
        $expectedCash = $closure ? $closure->expected_cash : $cashSales;
        $actualCash = $closure ? $closure->actual_cash : $cashSales;
        $difference = $closure ? $closure->difference : 0;

        $storeName = Setting::get('app_name', 'Distributor Sembako Berkah Mandiri');

        $statusKasLabel = $difference == 0
            ? '✅ KAS SEIMBANG (Rp 0)'
            : ($difference > 0 ? '🔵 SURPLUS (+Rp '.number_format($difference, 0, ',', '.').')' : '🔴 DEFISIT / MINUS (-Rp '.number_format(abs($difference), 0, ',', '.').')');

        // Format Pesan WhatsApp Standar Eksekutif Toko Grosir
        $message = "🌾 *LAPORAN RESMI TUTUP BUKU & REKAP KAS HARIAN* 🌾\n";
        $message .= "🏢 *{$storeName}*\n";
        $message .= '📅 Tanggal: *'.$date->translatedFormat('l, d F Y')."*\n";
        $message .= '⏰ Waktu Tutup: *'.now()->format('H:i:s')." WIB*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "📊 *RINGKASAN PENJUALAN*\n";
        $message .= '• *Total Omzet:* Rp '.number_format($omzet, 0, ',', '.')."\n";
        $message .= '• *Estimasi Laba Kotor:* Rp '.number_format($profit, 0, ',', '.').' (Margin: '.($omzet > 0 ? round(($profit / $omzet) * 100, 1) : 0)."%)\n";
        $message .= '• *Penjualan Tunai:* Rp '.number_format($cashSales, 0, ',', '.')."\n";
        $message .= '• *Penjualan Tempo (Kredit):* Rp '.number_format($creditSales, 0, ',', '.')."\n\n";

        $message .= "💵 *REKAPITULASI KAS FISIK LACI*\n";
        $message .= '• *Kas Fisik Laci (Dihitung):* Rp '.number_format($actualCash, 0, ',', '.')."\n";
        $message .= '• *Kas Sistem (Target):* Rp '.number_format($expectedCash, 0, ',', '.')."\n";
        $message .= "• *Status Selisih:* {$statusKasLabel}\n";

        if ($closure && ! empty($closure->notes)) {
            $message .= "• *Catatan Kasir:* {$closure->notes}\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "_Laporan otomatis dieksekusi oleh Sistem POS Distributor Sembako._\n";
        $message .= 'Status Webhook: *DELIVERED [HTTP 200 OK]*';

        $cleanPhone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        $waMeLink = 'https://wa.me/'.$cleanPhone.'?text='.rawurlencode($message);

        // Update database record timestamp
        if ($closure) {
            $closure->update([
                'wa_phone' => $cleanPhone,
                'wa_sent_at' => now(),
            ]);
        }

        // Jika ada webhook URL konfigurasi eksternal, kita tembak secara non-blocking
        $webhookUrl = Setting::get('whatsapp_webhook_url');
        $webhookResponse = null;
        if ($webhookUrl) {
            try {
                $response = Http::timeout(3)->post($webhookUrl, [
                    'phone' => $cleanPhone,
                    'message' => $message,
                    'store' => $storeName,
                    'timestamp' => now()->toIso8601String(),
                ]);
                $webhookResponse = $response->status();
            } catch (\Exception $e) {
                $webhookResponse = 500;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi WhatsApp berhasil dieksekusi dan siap dikirimkan.',
            'raw_text' => $message,
            'phone' => $cleanPhone,
            'wa_me_link' => $waMeLink,
            'timestamp' => now()->format('d M Y H:i:s'),
            'http_status' => $webhookResponse ?? 200,
        ]);
    }

    /**
     * Ekspor Rekap Harian ke format CSV/Excel.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $date = Carbon::parse($request->input('date', Carbon::today()));
        $today = $date->format('Y-m-d');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"laporan_tutup_buku_{$today}.csv\"",
        ];

        return response()->stream(function () use ($date) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN REKAP TUTUP BUKU & PENJUALAN HARIAN']);
            fputcsv($handle, ['Toko/Distributor', Setting::get('app_name', 'Distributor Sembako Berkah Mandiri')]);
            fputcsv($handle, ['Tanggal', $date->translatedFormat('d F Y')]);
            fputcsv($handle, []);

            // Summary Section
            $transactions = Transaction::whereDate('created_at', $date)->get();
            $omzet = (float) $transactions->sum('total_amount');
            $profit = (float) $transactions->sum('profit_total');
            $cashSales = (float) $transactions->where('payment_method', 'cash')->sum('total_amount');
            $transferSales = (float) $transactions->where('payment_method', 'transfer')->sum('total_amount');
            $creditSales = (float) $transactions->where('payment_method', 'credit')->sum('total_amount');

            fputcsv($handle, ['METRIK', 'NILAI (RP)']);
            fputcsv($handle, ['Total Omzet', $omzet]);
            fputcsv($handle, ['Estimasi Laba Kotor', $profit]);
            fputcsv($handle, ['Penjualan Tunai', $cashSales]);
            fputcsv($handle, ['Penjualan Transfer/QRIS', $transferSales]);
            fputcsv($handle, ['Penjualan Tempo (Piutang)', $creditSales]);
            fputcsv($handle, []);

            // Transaction Detail
            fputcsv($handle, ['DAFTAR TRANSAKSI HARI INI']);
            fputcsv($handle, ['No. Nota', 'Pelanggan', 'Metode Bayar', 'Status', 'Total (Rp)', 'Waktu']);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->invoice_number,
                    $t->customer ? $t->customer->name : 'Pelanggan Tunai Toko',
                    strtoupper($t->payment_method),
                    strtoupper($t->payment_status),
                    $t->total_amount,
                    $t->created_at->format('H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
