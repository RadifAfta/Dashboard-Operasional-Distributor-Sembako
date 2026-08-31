<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    /**
     * Display accounts receivable (Piutang & Jatuh Tempo).
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status'); // all, overdue, unpaid, partial, paid
        $today = Carbon::today();

        $query = Transaction::query()
            ->with(['customer', 'debtPayments.cashier', 'cashier'])
            ->where(function ($q) {
                $q->where('payment_method', 'credit')
                    ->orWhere('remaining_debt', '>', 0);
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            });

        if ($status === 'overdue') {
            $query->where('payment_status', '!=', 'paid')
                ->whereDate('due_date', '<', $today);
        } elseif ($status === 'unpaid') {
            $query->where('payment_status', 'unpaid');
        } elseif ($status === 'partial') {
            $query->where('payment_status', 'partial');
        } elseif ($status === 'paid') {
            $query->where('payment_status', 'paid');
        }

        $debts = $query->orderByRaw("CASE WHEN payment_status != 'paid' AND due_date < '{$today->format('Y-m-d')}' THEN 0 ELSE 1 END")
            ->orderBy($request->sort_field ?? 'due_date', $request->sort_direction ?? 'asc')
            ->paginate($request->per_page ?? 12)
            ->withQueryString();

        // Calculate summary cards
        $activeDebtsQuery = Transaction::where(function ($q) {
            $q->where('payment_method', 'credit')->orWhere('remaining_debt', '>', 0);
        })->where('payment_status', '!=', 'paid');

        $totalActiveDebt = (float) (clone $activeDebtsQuery)->sum('remaining_debt');

        $overdueQuery = (clone $activeDebtsQuery)->whereDate('due_date', '<', $today);
        $totalOverdueDebt = (float) (clone $overdueQuery)->sum('remaining_debt');
        $overdueCount = (clone $overdueQuery)->count();

        $paidThisMonth = (float) DebtPayment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $stats = [
            'total_active_debt' => $totalActiveDebt,
            'total_overdue_debt' => $totalOverdueDebt,
            'overdue_count' => $overdueCount,
            'paid_this_month' => $paidThisMonth,
        ];

        return Inertia::render('Admin/Debts/Index', [
            'debts' => $debts,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Settle debt (pelunasan parsial atau lunas seketika).
     */
    public function settle(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,transfer',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($transaction->remaining_debt <= 0) {
            return redirect()->back()->with('error', 'Nota transaksi ini sudah lunas sepenuhnya.');
        }

        $paymentAmount = min((float) $validated['amount'], (float) $transaction->remaining_debt);

        DB::transaction(function () use ($request, $transaction, $validated, $paymentAmount) {
            // 1. Record DebtPayment
            $transaction->debtPayments()->create([
                'customer_id' => $transaction->customer_id,
                'user_id' => $request->user()?->id,
                'amount' => $paymentAmount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Update Transaction balances
            $newPaidAmount = $transaction->paid_amount + $paymentAmount;
            $newRemainingDebt = max(0, $transaction->total_amount - $newPaidAmount);
            $newStatus = $newRemainingDebt <= 0 ? 'paid' : 'partial';

            $transaction->update([
                'paid_amount' => $newPaidAmount,
                'remaining_debt' => $newRemainingDebt,
                'payment_status' => $newStatus,
            ]);

            // 3. Update Customer Current Debt
            if ($transaction->customer_id) {
                Customer::where('id', $transaction->customer_id)->decrement('current_debt', $paymentAmount);
            }
        });

        $formattedAmount = 'Rp '.number_format($paymentAmount, 0, ',', '.');
        $statusMsg = $transaction->fresh()->payment_status === 'paid' ? 'LUNAS SEPENUHNYA' : 'Tercatat sebagai cicilan parsial';

        return redirect()->back()->with('success', "Pelunasan sebesar {$formattedAmount} untuk nota [{$transaction->invoice_number}] berhasil disimpan. Status: {$statusMsg}.");
    }
}
