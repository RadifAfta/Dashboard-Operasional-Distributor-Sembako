<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display listing of wholesale customers.
     */
    public function index(Request $request): Response
    {
        $customers = Customer::query()
            ->withCount(['transactions'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            })
            ->orderBy($request->sort_field ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        $stats = [
            'total_customers' => Customer::count(),
            'active_debtors' => Customer::where('current_debt', '>', 0)->count(),
            'total_outstanding_debt' => (float) Customer::sum('current_debt'),
        ];

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $count = Customer::count() + 1;
        $validated['code'] = 'CUST-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
        $validated['credit_limit'] = $validated['credit_limit'] ?? 10000000;
        $validated['current_debt'] = 0;
        $validated['status'] = 'active';

        $customer = Customer::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'message' => 'Pelanggan berhasil ditambahkan.',
            ]);
        }

        return redirect()->back()->with('success', "Pelanggan grosir [{$customer->name}] berhasil didaftarkan.");
    }

    /**
     * Update the customer.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($validated);

        return redirect()->back()->with('success', "Data pelanggan [{$customer->name}] berhasil diperbarui.");
    }

    /**
     * Remove the customer.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->current_debt > 0) {
            return redirect()->back()->with('error', "Pelanggan [{$customer->name}] masih memiliki sisa piutang Rp ".number_format($customer->current_debt, 0, ',', '.').' dan tidak dapat dihapus.');
        }

        $customer->delete();

        return redirect()->back()->with('success', 'Pelanggan berhasil dihapus.');
    }
}
