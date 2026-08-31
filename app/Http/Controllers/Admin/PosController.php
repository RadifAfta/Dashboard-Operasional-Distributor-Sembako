<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    /**
     * Display POS Kasir interface.
     */
    public function index(): Response
    {
        $products = Product::with('units')
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'name' => $p->name,
                    'category' => $p->category,
                    'cost_price' => (float) $p->cost_price,
                    'selling_price' => (float) $p->selling_price,
                    'base_unit' => $p->base_unit,
                    'current_stock' => (float) $p->current_stock,
                    'min_stock' => (float) $p->min_stock,
                    'is_critical' => $p->current_stock <= $p->min_stock,
                    'units' => $p->units->map(fn ($u) => [
                        'id' => $u->id,
                        'unit_name' => $u->unit_name,
                        'conversion_ratio' => (float) $u->conversion_ratio,
                        'selling_price' => (float) $u->selling_price,
                        'barcode' => $u->barcode,
                    ]),
                ];
            });

        $categories = Product::select('category')->distinct()->pluck('category');

        $customers = Customer::where('status', 'active')
            ->orderBy('name', 'asc')
            ->get(['id', 'code', 'name', 'phone', 'current_debt', 'credit_limit']);

        $nextInvoiceNumber = 'TRX-'.now()->format('Ymd').'-'.str_pad((string) (Transaction::whereDate('created_at', Carbon::today())->count() + 1), 4, '0', STR_PAD_LEFT);

        $storeInfo = [
            'name' => Setting::get('app_name', 'Distributor Sembako Berkah Mandiri'),
            'address' => Setting::get('store_address', 'Jl. Pasar Induk Kramat Jati Blok C No. 12, Jakarta Timur'),
            'phone' => Setting::get('store_phone', '0812-8888-9999'),
        ];

        return Inertia::render('Admin/POS/Index', [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'nextInvoiceNumber' => $nextInvoiceNumber,
            'storeInfo' => $storeInfo,
        ]);
    }

    /**
     * Process POS checkout.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_unit_id' => 'nullable|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,credit',
            'cash_given' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'customer_id' => 'required_if:payment_method,credit|nullable|exists:customers,id',
            'credit_days' => 'required_if:payment_method,credit|nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $createdTransaction = null;

        DB::transaction(function () use ($request, $validated, &$createdTransaction) {
            $subtotal = 0;
            $totalDiscount = 0;
            $costTotal = 0;
            $itemsToInsert = [];

            foreach ($validated['items'] as $itemData) {
                $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

                $unitName = $product->base_unit;
                $conversionRatio = 1.0;
                $sellingPrice = (float) $product->selling_price;
                $unitId = $itemData['product_unit_id'] ?? null;

                if ($unitId) {
                    $unit = ProductUnit::where('product_id', $product->id)->findOrFail($unitId);
                    $unitName = $unit->unit_name;
                    $conversionRatio = (float) $unit->conversion_ratio;
                    $sellingPrice = (float) $unit->selling_price;
                }

                $quantity = (float) $itemData['quantity'];
                $baseQuantity = $quantity * $conversionRatio;

                // Validate stock availability
                if ($product->current_stock < $baseQuantity) {
                    throw new \Exception("Stok tidak mencukupi untuk [{$product->name}]. Tersedia: {$product->current_stock} {$product->base_unit}, Diminta: {$baseQuantity} {$product->base_unit}.");
                }

                // Deduct stock in base units!
                $product->decrement('current_stock', $baseQuantity);

                $itemDiscount = (float) ($itemData['discount'] ?? 0);
                $itemSubtotal = ($sellingPrice * $quantity) - $itemDiscount;
                $itemCost = (float) $product->cost_price * $baseQuantity;
                $itemProfit = $itemSubtotal - $itemCost;

                $subtotal += ($sellingPrice * $quantity);
                $totalDiscount += $itemDiscount;
                $costTotal += $itemCost;

                $itemsToInsert[] = [
                    'product_id' => $product->id,
                    'product_unit_id' => $unitId,
                    'product_name' => $product->name,
                    'unit_name' => $unitName,
                    'conversion_ratio' => $conversionRatio,
                    'cost_price' => $product->cost_price,
                    'selling_price' => $sellingPrice,
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'discount_amount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                    'profit' => $itemProfit,
                ];
            }

            $totalAmount = max(0, $subtotal - $totalDiscount);
            $profitTotal = $totalAmount - $costTotal;

            $invoiceNumber = 'TRX-'.now()->format('Ymd').'-'.str_pad((string) (Transaction::whereDate('created_at', Carbon::today())->count() + 1), 4, '0', STR_PAD_LEFT);

            $paymentMethod = $validated['payment_method'];
            $paymentStatus = 'paid';
            $paidAmount = $totalAmount;
            $remainingDebt = 0;
            $dueDate = null;
            $cashGiven = null;
            $cashChange = null;

            if ($paymentMethod === 'cash') {
                $cashGiven = (float) ($validated['cash_given'] ?? $totalAmount);
                $cashChange = max(0, $cashGiven - $totalAmount);
                $paidAmount = $totalAmount;
            } elseif ($paymentMethod === 'transfer') {
                $paidAmount = $totalAmount;
            } elseif ($paymentMethod === 'credit') {
                $paymentStatus = 'unpaid';
                $paidAmount = 0;
                $remainingDebt = $totalAmount;
                $creditDays = (int) ($validated['credit_days'] ?? 14);
                $dueDate = Carbon::today()->addDays($creditDays)->format('Y-m-d');

                // Update customer current debt
                $customer = Customer::lockForUpdate()->findOrFail($validated['customer_id']);
                $customer->increment('current_debt', $totalAmount);
            }

            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'user_id' => $request->user()?->id,
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount,
                'cost_total' => $costTotal,
                'profit_total' => $profitTotal,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'cash_given' => $cashGiven,
                'cash_change' => $cashChange,
                'bank_name' => $validated['bank_name'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'due_date' => $dueDate,
                'credit_days' => $validated['credit_days'] ?? null,
                'paid_amount' => $paidAmount,
                'remaining_debt' => $remainingDebt,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemsToInsert as $item) {
                $transaction->items()->create($item);
            }

            $transaction->load(['items.product', 'customer', 'cashier']);
            $createdTransaction = $transaction;
        });

        return redirect()->back()
            ->with('success', "Transaksi [{$createdTransaction->invoice_number}] berhasil disimpan.")
            ->with('lastTransaction', $createdTransaction);
    }
}
