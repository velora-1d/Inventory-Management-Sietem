<?php

namespace Tests\Feature;

use App\DTOs\SaleData;
use App\Enums\PaymentMethod;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Unit;
use App\Models\User;
use App\Services\CashShiftService;
use App\Services\InventoryLedgerService;
use App\Services\SaleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoInventoryPosTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $manager;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cashier = User::factory()->create();
        $this->cashier->assignRole('cashier');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $category = Category::create([
            'name' => 'ATK Sekolah',
            'slug' => 'atk-sekolah',
        ]);
        $unit = Unit::create([
            'name' => 'Pieces',
            'symbol' => 'pcs',
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'ATK-001',
            'barcode' => '8990001112223',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'quantity' => 20,
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_inventory_ledger_enforces_available_calculation_and_anti_oversell(): void
    {
        $ledger = app(InventoryLedgerService::class);

        $this->assertSame(20, $ledger->availableStock($this->product->id));

        $reservation = $ledger->reserveStock(
            productId: $this->product->id,
            qty: 12,
            referenceType: 'order',
            referenceId: 101,
            reason: 'Customer checkout',
            orderId: 101,
            actorId: $this->cashier->id
        );

        $this->assertSame(8, $ledger->availableStock($this->product->id));
        $this->assertSame(20, $this->product->fresh()->quantity);

        $this->expectException(\RuntimeException::class);
        $ledger->reserveStock(
            productId: $this->product->id,
            qty: 9,
            referenceType: 'order',
            referenceId: 102,
            reason: 'Exceeding available stock',
            orderId: 102,
            actorId: $this->cashier->id
        );

        // Ensure release restores available stock idempotently
        $ledger->releaseReservation($reservation, 'Cancelled before picking', $this->cashier->id);
        $ledger->releaseReservation($reservation, 'Duplicate release', $this->cashier->id);
        $this->assertSame(20, $ledger->availableStock($this->product->id));
    }

    public function test_stock_movements_are_immutable_and_adjustments_record_audit_log(): void
    {
        $ledger = app(InventoryLedgerService::class);

        $movement = $ledger->adjustStock(
            productId: $this->product->id,
            deltaQty: -5,
            reason: 'Barang rusak di rak',
            actorId: $this->manager->id
        );

        $this->assertSame(15, $this->product->fresh()->quantity);
        $this->assertSame(15, $ledger->availableStock($this->product->id));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'stock.adjusted',
            'reason' => 'Barang rusak di rak',
        ]);

        try {
            $movement->update(['qty' => 999]);
            $this->fail('Expected LogicException when updating StockMovement.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', strtolower($e->getMessage()));
        }

        $this->expectException(\LogicException::class);
        $movement->delete();
    }

    public function test_inventory_pages_and_opname_approval_work_with_rbac(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->get('/inventory')->assertForbidden();
        $this->actingAs($this->cashier)->post('/inventory/adjust', [
            'product_id' => $this->product->id,
            'delta_qty' => 1,
            'reason' => 'Unauthorized',
        ])->assertForbidden();

        $warehouse = User::factory()->create();
        $warehouse->assignRole('warehouse');

        $this->actingAs($this->manager)->get('/inventory')->assertOk();
        $this->actingAs($this->manager)->get('/inventory/movements')->assertOk();
        $this->actingAs($this->manager)->get('/inventory/opnames')->assertOk();

        $this->actingAs($warehouse)
            ->post('/inventory/opnames', [
                'reason' => 'Audit stok bulanan',
                'items' => [
                    ['product_id' => $this->product->id, 'counted_qty' => 17],
                ],
            ])
            ->assertRedirect();

        $opname = StockOpname::query()->latest('id')->firstOrFail();

        $this->actingAs($this->manager)
            ->patch("/inventory/opnames/{$opname->id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $opname->fresh()->status);
        $this->assertSame(17, $this->product->fresh()->quantity);
        $this->assertTrue(AuditLog::where('action', 'stock.adjusted')->exists());
    }

    public function test_barcode_lookup_split_payments_and_cash_shift_close_variance(): void
    {
        $this->actingAs($this->cashier)
            ->getJson('/ajax/products/barcode/8990001112223')
            ->assertOk()
            ->assertJsonPath('id', $this->product->id)
            ->assertJsonPath('available', 20);

        $shiftService = app(CashShiftService::class);
        $shift = $shiftService->openShift($this->cashier, 100000);

        $saleService = app(SaleService::class);
        $sale = $saleService->createSale(SaleData::fromArray([
            'sale_date' => now()->toDateTimeString(),
            'payment_method' => PaymentMethod::CASH->value,
            'created_by' => $this->cashier->id,
            'cash_received' => 30000,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 10000, 'discount' => 0],
            ],
            'split_payments' => [
                ['payment_method' => PaymentMethod::CASH->value, 'amount' => 30000, 'reference' => 'CASH-1'],
                ['payment_method' => PaymentMethod::QRIS->value, 'amount' => 20000, 'reference' => 'QR-1'],
            ],
        ]));

        $this->assertSame(2, $sale->payments()->count());
        $this->assertSame(15, $this->product->fresh()->quantity);

        $closed = $shiftService->closeShift($shift, 128000, 'Selisih receh');
        $this->assertSame('130000.00', number_format((float) $closed->expected_closing_balance, 2, '.', ''));
        $this->assertSame('-2000.00', number_format((float) $closed->variance, 2, '.', ''));

        $this->actingAs($this->cashier)->get('/pos/shifts')->assertOk();
    }
}
