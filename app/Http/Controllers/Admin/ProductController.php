<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of the products with multi-units.
     */
    public function index(Request $request): Response
    {
        $category = $request->input('category');
        $status = $request->input('status');
        $isCritical = $request->boolean('is_critical');

        $products = Product::query()
            ->with('units')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhereHas('units', fn ($uq) => $uq->where('barcode', 'like', "%{$search}%"));
                });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($isCritical, fn ($q) => $q->whereColumn('current_stock', '<=', 'min_stock'))
            ->orderBy($request->sort_field ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 12)
            ->withQueryString();

        $categories = Product::select('category')->distinct()->pluck('category');

        $stats = [
            'total_products' => Product::count(),
            'critical_stock' => Product::whereColumn('current_stock', '<=', 'min_stock')->count(),
            'total_stock_value' => (float) Product::selectRaw('SUM(current_stock * cost_price) as total_val')->value('total_val'),
        ];

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'status', 'is_critical', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Store a newly created product with optional multi-units.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'base_unit' => 'required|string|max:50',
            'min_stock' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required|string|max:50',
            'units.*.conversion_ratio' => 'required|numeric|min:1',
            'units.*.selling_price' => 'required|numeric|min:0',
            'units.*.barcode' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated) {
            $units = $validated['units'] ?? [];
            unset($validated['units']);

            $product = Product::create($validated);

            foreach ($units as $unitData) {
                $product->units()->create($unitData);
            }
        });

        return redirect()->back()->with('success', "Produk [{$request->name}] dan satuan grosir berhasil ditambahkan.");
    }

    /**
     * Update the specified product and its multi-units.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => "required|string|max:50|unique:products,sku,{$product->id}",
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'base_unit' => 'required|string|max:50',
            'min_stock' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required|string|max:50',
            'units.*.conversion_ratio' => 'required|numeric|min:1',
            'units.*.selling_price' => 'required|numeric|min:0',
            'units.*.barcode' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($product, $validated) {
            $units = $validated['units'] ?? [];
            unset($validated['units']);

            $product->update($validated);

            // Re-sync multi-units
            $product->units()->delete();
            foreach ($units as $unitData) {
                $product->units()->create($unitData);
            }
        });

        return redirect()->back()->with('success', "Data produk [{$product->name}] berhasil diperbarui.");
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete();

        return redirect()->back()->with('success', "Produk [{$name}] berhasil dihapus.");
    }

    /**
     * Batch import products from JSON array (parsed from .xlsx / .csv on client or server).
     */
    public function batchImport(Request $request): RedirectResponse
    {
        $data = $request->input('items', []);

        if (empty($data) || ! is_array($data)) {
            return redirect()->back()->with('error', 'Tidak ada data produk yang diunggah untuk diimpor.');
        }

        $importedCount = 0;

        DB::transaction(function () use ($data, &$importedCount) {
            foreach ($data as $row) {
                if (empty($row['sku']) || empty($row['name'])) {
                    continue;
                }

                $product = Product::updateOrCreate(
                    ['sku' => trim($row['sku'])],
                    [
                        'barcode' => ! empty($row['barcode']) ? trim($row['barcode']) : null,
                        'name' => trim($row['name']),
                        'category' => ! empty($row['category']) ? trim($row['category']) : 'Sembako Umum',
                        'cost_price' => (float) ($row['cost_price'] ?? 0),
                        'selling_price' => (float) ($row['selling_price'] ?? 0),
                        'base_unit' => ! empty($row['base_unit']) ? trim($row['base_unit']) : 'Pcs',
                        'min_stock' => (float) ($row['min_stock'] ?? 10),
                        'current_stock' => (float) ($row['current_stock'] ?? 0),
                        'status' => 'active',
                    ]
                );

                // Check for wholesale unit in row
                if (! empty($row['wholesale_unit_name']) && ! empty($row['wholesale_ratio'])) {
                    ProductUnit::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'unit_name' => trim($row['wholesale_unit_name']),
                        ],
                        [
                            'conversion_ratio' => (float) $row['wholesale_ratio'],
                            'selling_price' => (float) ($row['wholesale_price'] ?? $product->selling_price * (float) $row['wholesale_ratio']),
                            'barcode' => ! empty($row['wholesale_barcode']) ? trim($row['wholesale_barcode']) : null,
                        ]
                    );
                }

                $importedCount++;
            }
        });

        return redirect()->back()->with('success', "Berhasil mengimpor {$importedCount} data barang sembako ke sistem.");
    }

    /**
     * Export all products with multi-units to CSV/Spreadsheet format.
     */
    public function export(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="master_barang_sembako_'.date('Ymd_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Kode SKU',
                'Barcode Dasar',
                'Nama Barang',
                'Kategori',
                'Harga Beli (HPP)',
                'Harga Jual Satuan',
                'Satuan Dasar',
                'Stok Min',
                'Stok Sekarang',
                'Satuan Grosir (Multi-Satuan)',
                'Status',
            ]);

            Product::with('units')->chunk(100, function ($products) use ($handle) {
                foreach ($products as $p) {
                    $unitsString = $p->units->map(function ($u) {
                        return "1 {$u->unit_name} = {$u->conversion_ratio} {$u->product->base_unit} (Rp ".number_format($u->selling_price, 0, ',', '.').')';
                    })->join(' | ');

                    fputcsv($handle, [
                        $p->sku,
                        $p->barcode,
                        $p->name,
                        $p->category,
                        $p->cost_price,
                        $p->selling_price,
                        $p->base_unit,
                        $p->min_stock,
                        $p->current_stock,
                        $unitsString,
                        $p->status,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download template import file for users.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_produk_sembako.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'sku',
                'barcode',
                'name',
                'category',
                'cost_price',
                'selling_price',
                'base_unit',
                'min_stock',
                'current_stock',
                'wholesale_unit_name',
                'wholesale_ratio',
                'wholesale_price',
                'wholesale_barcode',
            ]);

            // Sample rows
            fputcsv($handle, [
                'SKU-MNY-999',
                '8992753110999',
                'Minyak Goreng Sania 2L',
                'Minyak Goreng',
                '32500',
                '36000',
                'Pouch',
                '24',
                '120',
                'Dus (6 Pouch)',
                '6',
                '212000',
                '8992753110998',
            ]);

            fputcsv($handle, [
                'SKU-BRS-999',
                '8991234000999',
                'Beras Setra Ramos Super',
                'Beras & Padi',
                '13200',
                '14800',
                'Kg',
                '100',
                '500',
                'Sak (25 Kg)',
                '25',
                '360000',
                '8991234000998',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
