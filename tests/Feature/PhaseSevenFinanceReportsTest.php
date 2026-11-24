<?php

namespace Tests\Feature;

use App\Enums\FinanceCategoryType;
use App\Models\AccountsPayable;
use App\Models\Category;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountsPayableService;
use App\Services\CustomerOrderService;
use App\Services\InventoryLedgerService;
use App\Services\ProcurementService;
use App\Services\RefundService;
use Database\Seeders\FinanceCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseSevenFinanceReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $purchasing;
    private User $manager;
    private User $warehouse;
    private User $finance;
    private User $cashier;
    private User $customer;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(FinanceCategorySeeder::class);
        $this->seed(WorkflowSeeder::class);

        $this->purchasing = User::factory()->create(['name' => 'Purchasing Staff']);
        $this->purchasing->assignRole('purchasing');

        $this->manager = User::factory()->create(['name' => 'Manager Staff']);
        $this->manager->assignRole('manager');

        $this->warehouse = User::factory()->create(['name' => 'Warehouse Staff']);
        $this->warehouse->assignRole('warehouse');

        $this->finance = User::factory()->create(['name' => 'Finance Staff']);
        $this->finance->assignRole('finance');

        $this->cashier = User::factory()->create(['name' => 'Cashier Staff']);
        $this->cashier->assignRole('cashier');

        $this->customer = User::factory()->create(['name' => 'Customer Pembeli']);
        $this->customer->assignRole('customer');

        $this->supplier = Supplier::factory()->create();

        $category = Category::create([
            'name' => 'Alat Tulis',
            'slug' => 'alat-tulis',
        ]);

        $unit = Unit::create([
            'name' => 'Box',
            'symbol' => 'box',
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'FIN-001',
            'purchase_price' => 10000,
            'selling_price' => 25000,
            'quantity' => 20,
            'min_stock' => 2,
            'is_active' => true,
        ]);
    }

    public function test_accounts_payable_partial_and_full_payment_records_expense_transactions(): void
    {
        $procurement = app(ProcurementService::class);
        $apService = app(AccountsPayableService::class);
        $warehouseId = app(InventoryLedgerService::class)->defaultWarehouse()->id;

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
            notes: 'PO untuk uji AP'
        );

        $po = $procurement->submitPurchaseOrder($po, $this->purchasing);
        $po = $procurement->approvePurchaseOrder($po, $this->manager, 'Approved');

        $poItem = $po->items()->firstOrFail();
        $grn = $procurement->receiveGoods(
            po: $po,
            receiver: $this->warehouse,
            receivedItems: [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'quantity_received' => 10,
                    'quantity_accepted' => 10,
                    'quantity_rejected' => 0,
                ],
            ],
            warehouseId: $warehouseId,
            qcStatus: 'passed'
        );

        $invoice = $procurement->recordSupplierInvoice($po, $this->finance, [
            'invoice_number' => 'INV-AP-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'amount' => 100000,
            'goods_receipt_id' => $grn->id,
        ]);

        $ap = AccountsPayable::where('supplier_invoice_id', $invoice->id)->first()
            ?? $apService->createFromSupplierInvoice($invoice);

        $txCountBefore = FinanceTransaction::count();

        // Partial payment: 40,000 of 100,000
        $apService->recordPayment(
            ap: $ap,
            actor: $this->finance,
            amount: 40000,
            paymentMethod: 'bank_transfer',
            paymentDate: now()->toDateString(),
            reference: 'TRF-AP-01',
            notes: 'Pembayaran termin 1'
        );

        $ap->refresh();
        $this->assertSame('60000.00', number_format((float) $ap->balance_due, 2, '.', ''));
        $this->assertNotSame('paid', $ap->status);
        $this->assertSame($txCountBefore + 1, FinanceTransaction::count());

        $firstTx = FinanceTransaction::with('category')->latest('id')->firstOrFail();
        $this->assertSame(40000, (int) $firstTx->amount);
        $this->assertSame(FinanceCategoryType::Expense, $firstTx->category?->type);

        // Full remaining payment: 60,000
        $apService->recordPayment(
            ap: $ap,
            actor: $this->finance,
            amount: 60000,
            paymentMethod: 'bank_transfer',
            paymentDate: now()->toDateString(),
            reference: 'TRF-AP-02',
            notes: 'Pelunasan AP'
        );

        $ap->refresh();
        $this->assertSame('0.00', number_format((float) $ap->balance_due, 2, '.', ''));
        $this->assertSame('paid', $ap->status);
        $this->assertSame($txCountBefore + 2, FinanceTransaction::count());

        $secondTx = FinanceTransaction::with('category')->latest('id')->firstOrFail();
        $this->assertSame(60000, (int) $secondTx->amount);
        $this->assertSame(FinanceCategoryType::Expense, $secondTx->category?->type);
    }

    public function test_refund_lifecycle_creates_expense_and_updates_payment_status_without_clobbering_order_or_shipment_status(): void
    {
        $orderService = app(CustomerOrderService::class);
        $refundService = app(RefundService::class);

        $order = $orderService->placeOrder(
            $this->customer,
            [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            [
                'delivery_type' => 'delivery',
                'recipient_name' => 'Budi Santoso',
                'phone' => '081234567890',
                'address_line' => 'Jl. Sudirman No. 10',
                'city' => 'Jakarta',
                'payment_method' => 'manual_transfer',
            ]
        );

        $order = $orderService->verifyPayment($order, $this->finance, true, 'Pembayaran diterima');
        $order->refresh();

        $originalOrderStatus = $order->status;
        $originalShipmentStatus = $order->shipment_status;
        $txCountBefore = FinanceTransaction::count();

        $refund = $refundService->requestRefund(
            actor: $this->customer,
            amount: (float) $order->total,
            method: 'bank_transfer',
            reason: 'Pelanggan membatalkan sebelum kirim',
            orderId: $order->id,
            saleId: null
        );

        $refund = $refundService->approveRefund($refund, $this->manager);
        $refund = $refundService->processRefund($refund, $this->finance);

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame($originalOrderStatus, $order->status);
        $this->assertSame($originalShipmentStatus, $order->shipment_status);

        $this->assertSame($txCountBefore + 1, FinanceTransaction::count());
        $refundTx = FinanceTransaction::with('category')->latest('id')->firstOrFail();
        $this->assertSame((int) $order->total, (int) $refundTx->amount);
        $this->assertSame(FinanceCategoryType::Expense, $refundTx->category?->type);

        // Verify rejectRefund works cleanly on another order
        $order2 = $orderService->placeOrder(
            $this->customer,
            [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            [
                'delivery_type' => 'pickup',
                'payment_method' => 'manual_transfer',
            ]
        );
        $order2 = $orderService->verifyPayment($order2, $this->finance, true, 'Verified');

        $refund2 = $refundService->requestRefund(
            actor: $this->customer,
            amount: (float) $order2->total,
            method: 'bank_transfer',
            reason: 'Ubah pikiran',
            orderId: $order2->id,
            saleId: null
        );

        $rejected = $refundService->rejectRefund($refund2, $this->manager, 'Tidak memenuhi syarat');
        $this->assertSame('rejected', $rejected->status);
        $this->assertSame('paid', $order2->fresh()->payment_status);
    }

    public function test_finance_ap_refunds_reports_and_csv_export_enforce_rbac(): void
    {
        $routes = [
            '/finance/ap',
            '/finance/refunds',
            '/reports',
        ];

        foreach ($routes as $uri) {
            $this->actingAs($this->finance)->get($uri)->assertOk();
            $this->actingAs($this->customer)->get($uri)->assertForbidden();
            $this->actingAs($this->cashier)->get($uri)->assertForbidden();
        }

        $csvResponse = $this->actingAs($this->finance)->get('/reports/export/sales');
        $csvResponse->assertOk();
        $this->assertStringContainsString(
            'text/csv',
            strtolower((string) $csvResponse->headers->get('content-type'))
        );

        $this->actingAs($this->customer)->get('/reports/export/sales')->assertForbidden();
        $this->actingAs($this->cashier)->get('/reports/export/sales')->assertForbidden();
    }
}
