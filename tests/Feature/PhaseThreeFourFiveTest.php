<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\StockReservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryLedgerService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseThreeFourFiveTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(WorkflowSeeder::class);

        $category = Category::create(['name' => 'Elektronik', 'slug' => 'elektronik']);
        $unit = Unit::create(['name' => 'Unit', 'symbol' => 'unit']);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'ELK-001',
            'purchase_price' => 50000,
            'selling_price' => 125000,
            'quantity' => 15,
            'min_stock' => 2,
            'is_active' => true,
        ]);
    }

    public function test_customer_registration_checkout_server_side_pricing_and_cancel_releases_stock(): void
    {
        $this->get('/shop')->assertOk()->assertSee($this->product->name);

        $this->post('/shop/register', [
            'name' => 'Budi Pembeli',
            'email' => 'budi.buyer@example.com',
            'phone' => '081299998888',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/shop');

        $buyer = User::where('email', 'budi.buyer@example.com')->firstOrFail();
        $this->assertTrue($buyer->hasRole('customer'));

        // Attempt to tamper price in checkout payload; server MUST use 125000
        $this->actingAs($buyer)
            ->post('/shop/checkout', [
                'delivery_type' => 'delivery',
                'recipient_name' => 'Budi Secret Rahasia',
                'phone' => '081299998888',
                'address_line' => 'Jl. Merdeka Rahasia No. 99',
                'city' => 'Bandung',
                'payment_method' => 'manual_transfer',
                'notes' => 'Tolong jangan bocor internal note',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 1],
                ],
            ])
            ->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('375000.00', number_format((float) $order->total, 2, '.', ''));
        $this->assertSame('pending_payment', $order->status);

        $ledger = app(InventoryLedgerService::class);
        $this->assertSame(12, $ledger->availableStock($this->product->id));

        // Cancel order before picking and verify stock reservation is released
        $this->actingAs($buyer)
            ->post("/my-orders/{$order->id}/cancel", ['reason' => 'Ganti metode'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(15, $ledger->availableStock($this->product->id));
        $this->assertSame(
            'released',
            StockReservation::where('order_id', $order->id)->firstOrFail()->status
        );
    }

    public function test_anti_idor_prevents_other_customer_from_accessing_or_cancelling_order(): void
    {
        $customerA = User::factory()->create();
        $customerA->assignRole('customer');

        $customerB = User::factory()->create();
        $customerB->assignRole('customer');

        $this->actingAs($customerA)->post('/shop/checkout', [
            'delivery_type' => 'pickup',
            'payment_method' => 'manual_transfer',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->actingAs($customerA)->get("/my-orders/{$order->id}")->assertOk();
        $this->actingAs($customerB)->get("/my-orders/{$order->id}")->assertForbidden();
        $this->actingAs($customerB)->post("/my-orders/{$order->id}/cancel", ['reason' => 'Hack'])->assertForbidden();
        $this->actingAs($customerB)->get('/orders')->assertForbidden();
    }

    public function test_full_fulfillment_workflow_dependencies_qc_revision_shipping_and_privacy_tracking(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create(['name' => 'Siti Customer']);
        $customer->assignRole('customer');

        $admin = User::factory()->create(['name' => 'Admin Staff']);
        $admin->assignRole('admin');

        $warehouseA = User::factory()->create(['name' => 'Gudang Staff A']);
        $warehouseA->assignRole('warehouse');
        $warehouseB = User::factory()->create(['name' => 'Gudang Staff B']);
        $warehouseB->assignRole('warehouse');

        $qcUser = User::factory()->create(['name' => 'QC Officer Rahasia']);
        $qcUser->assignRole('qc_packing');

        $shippingUser = User::factory()->create(['name' => 'Kurir Internal']);
        $shippingUser->assignRole('shipping');

        $manager = User::factory()->create(['name' => 'Manager Operasional']);
        $manager->assignRole('manager');

        // 1. Place order & upload payment proof
        $this->actingAs($customer)->post('/shop/checkout', [
            'delivery_type' => 'delivery',
            'recipient_name' => 'Siti Private Address Name',
            'phone' => '081377776666',
            'address_line' => 'Jl. Rahasia Negara No. 404',
            'city' => 'Cirebon',
            'payment_method' => 'manual_transfer',
            'notes' => 'Catatan pribadi pelanggan',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 4],
            ],
        ])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->actingAs($customer)->post("/my-orders/{$order->id}/proof", [
            'proof' => UploadedFile::fake()->image('bukti-tf.png'),
        ])->assertRedirect();

        $this->assertSame('waiting_verification', $order->fresh()->payment_status);

        // 2. Admin verifies payment -> Confirmed & auto-generates 4 workflow tasks
        $this->actingAs($admin)->post("/orders/{$order->id}/verify-payment", [
            'decision' => 'approve',
            'reason' => 'Mutasi bank valid',
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);

        $tasks = OrderTask::where('order_id', $order->id)->orderBy('id')->get();
        $this->assertCount(4, $tasks);

        [$picking, $qc, $packing, $readyToShip] = $tasks->all();

        // 3. Dependency guard: QC cannot start before Picking is completed
        $this->actingAs($qcUser)->patch("/work/tasks/{$qc->id}/start")->assertRedirect();
        $this->assertNotSame('in_progress', $qc->fresh()->status);

        // Manager reassigns picking task to warehouseB with mandatory reason
        $this->actingAs($manager)->patch("/work/tasks/{$picking->id}/reassign", [
            'assigned_to' => $warehouseB->id,
            'reason' => 'Gudang A sedang overload',
        ])->assertRedirect();

        $this->assertSame($warehouseB->id, $picking->fresh()->assigned_to);
        $this->assertTrue(AuditLog::where('action', 'task.reassigned')->exists());

        // Picking starts & completes
        $this->actingAs($warehouseB)->patch("/work/tasks/{$picking->id}/start")->assertRedirect();
        $this->actingAs($warehouseB)->patch("/work/tasks/{$picking->id}/complete")->assertRedirect();
        $this->assertSame('completed', $picking->fresh()->status);

        // QC starts & requests revision with attachment
        $this->actingAs($qcUser)->patch("/work/tasks/{$qc->id}/start")->assertRedirect();
        $this->actingAs($qcUser)->patch("/work/tasks/{$qc->id}/revision", [
            'reason' => 'Segel dus perlu dirapikan',
            'attachment' => UploadedFile::fake()->image('revisi.png'),
        ])->assertRedirect();
        $this->assertSame('revision', $qc->fresh()->status);

        // Complete QC, Packing (consumes stock reservation!), and Ready to Ship
        $this->actingAs($qcUser)->patch("/work/tasks/{$qc->id}/complete")->assertRedirect();
        $this->actingAs($qcUser)->patch("/work/tasks/{$packing->id}/start")->assertRedirect();
        $this->actingAs($qcUser)->patch("/work/tasks/{$packing->id}/complete")->assertRedirect();

        $ledger = app(InventoryLedgerService::class);
        $this->assertSame(11, $this->product->fresh()->quantity);
        $this->assertSame(11, $ledger->availableStock($this->product->id));
        $this->assertSame(
            'consumed',
            StockReservation::where('order_id', $order->id)->firstOrFail()->status
        );

        $this->actingAs($shippingUser)->patch("/work/tasks/{$readyToShip->id}/start")->assertRedirect();
        $this->actingAs($shippingUser)->patch("/work/tasks/{$readyToShip->id}/complete")->assertRedirect();

        // 4. Shipping dispatch & customer delivery confirmation
        $this->actingAs($shippingUser)->post("/shipping/orders/{$order->id}/dispatch", [
            'carrier' => 'JNE',
            'service' => 'REG',
            'tracking_number' => 'JNE-1234567890',
        ])->assertRedirect();

        $this->assertSame('shipped', $order->fresh()->status);
        $shipment = Shipment::where('order_id', $order->id)->firstOrFail();

        $this->actingAs($customer)->post("/shipping/shipments/{$shipment->id}/confirm-delivery", [
            'notes' => 'Paket diterima baik',
        ])->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->shipment_status);

        // 5. Public tracking endpoint privacy assertion (MUST NOT leak PII or PIC names)
        $trackingHtml = $this->get("/track/{$order->public_tracking_token}")->assertOk()->getContent();
        $trackingJson = $this->getJson("/api/v1/track/{$order->public_tracking_token}")->assertOk()->getContent();

        foreach ([$trackingHtml, $trackingJson] as $payload) {
            $this->assertStringContainsString($order->order_number, $payload);
            $this->assertStringContainsString('Pesanan selesai', $payload);
            $this->assertStringNotContainsString('Jl. Rahasia Negara No. 404', $payload);
            $this->assertStringNotContainsString('081377776666', $payload);
            $this->assertStringNotContainsString('Siti Private Address Name', $payload);
            $this->assertStringNotContainsString('QC Officer Rahasia', $payload);
            $this->assertStringNotContainsString('Gudang A sedang overload', $payload);
        }

        // Staff views render cleanly
        $this->actingAs($admin)->get('/orders')->assertOk();
        $this->actingAs($admin)->get("/orders/{$order->id}")->assertOk();
        $this->actingAs($manager)->get('/work/board')->assertOk();
        $this->actingAs($shippingUser)->get('/shipping')->assertOk();
    }
}
