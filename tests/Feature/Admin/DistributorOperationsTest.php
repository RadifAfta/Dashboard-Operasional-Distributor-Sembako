<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\DailyCashClosure;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::first() ?? User::factory()->create();
    }

    public function test_dashboard_renders_with_distributor_metrics_and_chart(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics')
            ->has('salesChart')
            ->has('quickAlertProducts')
        );
    }

    public function test_can_create_product_with_multi_units(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'sku' => 'SKU-TEST-001',
            'barcode' => '89999990001',
            'name' => 'Kopi Kapal Api Special Mix',
            'category' => 'Minuman & Kopi',
            'cost_price' => 1200,
            'selling_price' => 1500,
            'base_unit' => 'Sachet',
            'min_stock' => 50,
            'current_stock' => 500,
            'status' => 'active',
            'units' => [
                [
                    'unit_name' => 'Renceng (10 Sachet)',
                    'conversion_ratio' => 10,
                    'selling_price' => 14000,
                    'barcode' => '89999990002',
                ],
                [
                    'unit_name' => 'Dus (120 Sachet)',
                    'conversion_ratio' => 120,
                    'selling_price' => 160000,
                    'barcode' => '89999990003',
                ],
            ],
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-TEST-001',
            'base_unit' => 'Sachet',
        ]);

        $product = Product::where('sku', 'SKU-TEST-001')->first();
        $this->assertCount(2, $product->units);
    }

    public function test_pos_checkout_with_multi_unit_deducts_base_stock(): void
    {
        $product = Product::first();
        $unit = $product->units()->first();

        $initialStock = (float) $product->current_stock;
        $qtyToBuy = 2;
        $conversionRatio = $unit ? (float) $unit->conversion_ratio : 1;

        $response = $this->actingAs($this->admin)->post(route('admin.pos.checkout'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_unit_id' => $unit?->id,
                    'quantity' => $qtyToBuy,
                    'discount' => 0,
                ],
            ],
            'payment_method' => 'cash',
            'cash_given' => 10000000,
        ]);

        $response->assertSessionHas('success');

        $product->refresh();
        $expectedStock = $initialStock - ($qtyToBuy * $conversionRatio);
        $this->assertEquals($expectedStock, $product->current_stock);
    }

    public function test_pos_checkout_tempo_creates_unpaid_transaction_with_due_date(): void
    {
        $customer = Customer::first();
        $product = Product::first();

        $initialCustomerDebt = (float) $customer->current_debt;

        $response = $this->actingAs($this->admin)->post(route('admin.pos.checkout'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_unit_id' => null,
                    'quantity' => 5,
                    'discount' => 0,
                ],
            ],
            'payment_method' => 'credit',
            'customer_id' => $customer->id,
            'credit_days' => 14,
            'notes' => 'Tempo 14 hari',
        ]);

        $response->assertSessionHas('success');

        $transaction = Transaction::latest('id')->first();
        $this->assertEquals('credit', $transaction->payment_method);
        $this->assertEquals('unpaid', $transaction->payment_status);
        $this->assertEquals(Carbon::today()->addDays(14)->format('Y-m-d'), $transaction->due_date->format('Y-m-d'));

        $customer->refresh();
        $this->assertGreaterThan($initialCustomerDebt, (float) $customer->current_debt);
    }

    public function test_can_settle_overdue_debt_partially_and_fully(): void
    {
        $overdueTrx = Transaction::where('payment_status', '!=', 'paid')->first();

        $this->assertNotNull($overdueTrx);

        // 1. Pelunasan parsial
        $partialAmount = min(500000, (float) $overdueTrx->remaining_debt);

        $response = $this->actingAs($this->admin)->post(route('admin.debts.settle', $overdueTrx->id), [
            'amount' => $partialAmount,
            'payment_method' => 'cash',
            'notes' => 'Cicilan pertama',
        ]);

        $response->assertSessionHas('success');

        $overdueTrx->refresh();
        $this->assertDatabaseHas('debt_payments', [
            'transaction_id' => $overdueTrx->id,
            'amount' => $partialAmount,
        ]);

        // 2. Pelunasan penuh
        $remaining = (float) $overdueTrx->remaining_debt;
        $responseFull = $this->actingAs($this->admin)->post(route('admin.debts.settle', $overdueTrx->id), [
            'amount' => $remaining,
            'payment_method' => 'transfer',
            'reference_number' => 'REF-LUNAS-999',
        ]);

        $responseFull->assertSessionHas('success');
        $overdueTrx->refresh();
        $this->assertEquals('paid', $overdueTrx->payment_status);
        $this->assertEquals(0, (float) $overdueTrx->remaining_debt);
    }

    public function test_daily_closure_calculates_cash_drawer_difference_and_triggers_wa(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.closure.store'), [
            'actual_cash' => 5000000,
            'expected_cash' => 4950000,
            'total_omzet' => 12000000,
            'total_profit' => 1800000,
            'total_cash_sales' => 4950000,
            'total_credit_sales' => 7050000,
            'denominations' => [
                'd100k' => 50,
            ],
            'notes' => 'Surplus Rp 50.000',
            'wa_phone' => '6281288889999',
        ]);

        $response->assertSessionHas('success');

        $closure = DailyCashClosure::where('closure_date', Carbon::today()->format('Y-m-d'))->first();
        $this->assertNotNull($closure);
        $this->assertEquals(50000, (float) $closure->difference); // Surplus 50k

        // Test WhatsApp Trigger Endpoint
        $waResponse = $this->actingAs($this->admin)->postJson(route('admin.closure.whatsapp'), [
            'phone' => '081288889999',
            'closure_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $waResponse->assertStatus(200);
        $waResponse->assertJsonStructure([
            'success',
            'raw_text',
            'phone',
            'wa_me_link',
            'http_status',
        ]);
    }
}
