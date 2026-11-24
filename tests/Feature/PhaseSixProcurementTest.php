<?php

namespace Tests\Feature;

use App\Models\AccountsPayable;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryLedgerService;
use App\Services\ProcurementService;
use Database\Seeders\FinanceCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PhaseSixProcurementTest extends TestCase
{
    use RefreshDatabase;

    private User $purchasing;
    private User $manager;
    private User $warehouse;
    private User $finance;
    private User $customer;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(FinanceCategorySeeder::class);

        $this->purchasing = User::factory()->create(['name' => 'Purchasing Staff']);
        $this->purchasing->assignRole('purchasing');

        $this->manager = User::factory()->create(['name' => 'Operations Manager']);
        $this->manager->assignRole('manager');

        $this->warehouse = User::factory()->create(['name' => 'Warehouse Staff']);
        $this->warehouse->assignRole('warehouse');

        $this->finance = User::factory()->create(['name' => 'Finance Staff']);
        $this->finance->assignRole('finance');

        $this->customer = User::factory()->create(['name' => 'Customer Biasa']);
        $this->customer->assignRole('customer');

        $this->supplier = Supplier::factory()->create();

        $category = Category::create([
            'name' => 'Bahan Baku',
            'slug' => 'bahan-baku',
        ]);

        $unit = Unit::create([
            'name' => 'Pieces',
            'symbol' => 'pcs',
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'PROC-001',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'quantity' => 10,
            'min_stock' => 2,
            'is_active' => true,
        ]);
    }

    public function test_full_procurement_lifecycle_maker_checker_revision_grn_three_way_match_and_return(): void
    {
        $procurement = app(ProcurementService::class);
        $ledger = app(InventoryLedgerService::class);
        $defaultWarehouse = $ledger->defaultWarehouse();

        // 1. PR creation -> manager approval
        $pr = $procurement->createPurchaseRequest(
            requester: $this->purchasing,
            items: [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'estimated_unit_price' => 10000,
                ],
            ],
            notes: 'Restok gudang utama'
        );

        $approvedPr = $procurement->approvePurchaseRequest($pr, $this->manager, 'Disetujui manager');
        $this->assertSame('approved', $approvedPr->status);

        // 2. PO creation & submission -> maker-checker prevents creator from approving own PO
        $po = $procurement->createPurchaseOrder(
            creator: $this->purchasing,
            supplierId: $this->supplier->id,
            items: [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 10,
                    'unit_price' => 10000,
                ],
            ],
            purchaseRequestId: $approvedPr->id,
            expectedDate: now()->addDays(5)->toDateString(),
            notes: 'PO awal'
        );

        $po = $procurement->submitPurchaseOrder($po, $this->purchasing);
        $this->assertSame('pending_approval', $po->status);

        $makerCheckerThrown = false;
        try {
            $procurement->approvePurchaseOrder($po, $this->purchasing, 'Self approve attempt');
        } catch (\DomainException $e) {
            $makerCheckerThrown = true;
        }
        $this->assertTrue($makerCheckerThrown, 'Creator must not be able to approve own PO.');

        // Also assert HTTP maker-checker / unauthorized approval fails cleanly
        $selfPo = $procurement->createPurchaseOrder(
            creator: $this->manager,
            supplierId: $this->supplier->id,
            items: [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 5,
                    'unit_price' => 10000,
                ],
            ],
            notes: 'Manager self-created PO'
        );
        $selfPo = $procurement->submitPurchaseOrder($selfPo, $this->manager);

        $approveResponse = $this->callApproveRoute($this->manager, $selfPo);
        $this->assertNotSame('approved', $selfPo->fresh()->status);
        $this->assertTrue(
            $approveResponse->getStatusCode() >= 400
                || ($approveResponse->isRedirection() && session()->has('error')),
            'Expected HTTP error or error flash when creator tries to approve own PO.'
        );

        // Manager approves purchasing's PO -> incoming balance increases
        $po = $procurement->approvePurchaseOrder($po, $this->manager, 'Approved by manager');
        $this->assertSame('approved', $po->status);

        $balance = StockBalance::where('warehouse_id', $defaultWarehouse->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();
        $this->assertSame(10, (int) $balance->incoming);

        // 3. PO revision resets status to pending_approval and rolls back incoming until re-approved
        $po = $procurement->revisePurchaseOrder(
            po: $po,
            actor: $this->purchasing,
            items: [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 12,
                    'unit_price' => 10000,
                ],
            ],
            reason: 'Tambah qty menjadi 12 pcs'
        );

        $this->assertSame('pending_approval', $po->status);
        $this->assertSame(0, (int) $balance->fresh()->incoming);

        // Re-approve revised PO -> incoming becomes 12
        $po = $procurement->approvePurchaseOrder($po, $this->manager, 'Re-approved after revision');
        $this->assertSame('approved', $po->status);
        $this->assertSame(12, (int) $balance->fresh()->incoming);

        // 4. Partial Goods Receipt (5 of 12) -> moves accepted qty from incoming to on_hand (IN)
        $poItem = $po->items()->firstOrFail();
        $grn1 = $procurement->receiveGoods(
            po: $po,
            receiver: $this->warehouse,
            receivedItems: [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'quantity_received' => 5,
                    'quantity_accepted' => 5,
                    'quantity_rejected' => 0,
                ],
            ],
            warehouseId: $defaultWarehouse->id,
            qcStatus: 'passed',
            notes: 'Pengiriman parsial tahap 1'
        );

        $po->refresh();
        $balance->refresh();
        $this->assertSame('partially_received', $po->status);
        $this->assertSame(7, (int) $balance->incoming);
        $this->assertSame(15, (int) $balance->on_hand);
        $this->assertTrue(
            StockMovement::where('product_id', $this->product->id)
                ->where('type', StockMovement::TYPE_IN)
                ->where('qty', 5)
                ->exists()
        );

        // Final Goods Receipt (remaining 7) -> PO becomes fully_received
        $grn2 = $procurement->receiveGoods(
            po: $po,
            receiver: $this->warehouse,
            receivedItems: [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'quantity_received' => 7,
                    'quantity_accepted' => 7,
                    'quantity_rejected' => 0,
                ],
            ],
            warehouseId: $defaultWarehouse->id,
            qcStatus: 'passed',
            notes: 'Pelunasan pengiriman tahap 2'
        );

        $po->refresh();
        $balance->refresh();
        $this->assertSame('fully_received', $po->status);
        $this->assertSame(0, (int) $balance->incoming);
        $this->assertSame(22, (int) $balance->on_hand);

        // 5. 3-Way Match: mismatched amount -> discrepancy; matched amount -> matched + creates AccountsPayable
        $discrepancyInvoice = $procurement->recordSupplierInvoice($po, $this->finance, [
            'invoice_number' => 'SINV-ERR-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'amount' => 150000,
            'goods_receipt_id' => $grn1->id,
        ]);

        $this->assertSame('discrepancy', $discrepancyInvoice->match_status);
        $this->assertFalse(
            AccountsPayable::where('supplier_invoice_id', $discrepancyInvoice->id)->exists()
        );

        $matchedInvoice = $procurement->recordSupplierInvoice($po, $this->finance, [
            'invoice_number' => 'SINV-OK-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'amount' => 120000,
            'goods_receipt_id' => $grn2->id,
        ]);

        $this->assertSame('matched', $matchedInvoice->match_status);
        $this->assertTrue(
            AccountsPayable::where('supplier_invoice_id', $matchedInvoice->id)->exists()
        );

        // 6. Purchase Return -> records StockMovement RETURN_OUT & decrements on_hand
        $procurement->createPurchaseReturn(
            po: $po,
            actor: $this->warehouse,
            items: [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                ],
            ],
            reason: 'Kemasan rusak saat bongkar muat',
            goodsReceiptId: $grn2->id
        );

        $this->assertSame(20, (int) $balance->fresh()->on_hand);
        $this->assertTrue(
            StockMovement::where('product_id', $this->product->id)
                ->where('type', StockMovement::TYPE_RETURN_OUT)
                ->where('qty', 2)
                ->exists()
        );
    }

    public function test_procurement_http_endpoints_render_and_enforce_rbac(): void
    {
        $endpoints = [
            '/procurement',
            '/procurement/orders',
            '/procurement/receipts',
            '/procurement/invoices',
            '/procurement/returns',
        ];

        foreach ($endpoints as $uri) {
            $this->actingAs($this->purchasing)->get($uri)->assertOk();
            $this->actingAs($this->manager)->get($uri)->assertOk();
            $this->actingAs($this->customer)->get($uri)->assertForbidden();
        }
    }

    private function callApproveRoute(User $actor, PurchaseOrder $po)
    {
        if (Route::has('procurement.orders.approve')) {
            $route = app('router')->getRoutes()->getByName('procurement.orders.approve');
            $methods = array_diff($route->methods(), ['HEAD']);
            $method = in_array('POST', $methods, true) ? 'POST' : (reset($methods) ?: 'POST');

            return $this->actingAs($actor)->call($method, route('procurement.orders.approve', $po));
        }

        return $this->actingAs($actor)->post("/procurement/orders/{$po->id}/approve");
    }
}
