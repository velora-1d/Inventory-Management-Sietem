<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderTask;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\TaskAttachment;
use App\Models\Unit;
use App\Models\User;
use App\Services\CustomerOrderService;
use App\Services\IdempotencyService;
use App\Services\ShippingTrackingService;
use App\Support\SecurityRateLimiters;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class PhaseEightSecurityUatTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(WorkflowSeeder::class);

        $category = Category::create([
            'name' => 'Keamanan',
            'slug' => 'keamanan',
        ]);

        $unit = Unit::create([
            'name' => 'Pieces',
            'symbol' => 'pcs',
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SEC-001',
            'purchase_price' => 25000,
            'selling_price' => 50000,
            'quantity' => 25,
            'min_stock' => 2,
            'is_active' => true,
        ]);
    }

    public function test_security_rate_limiters_are_registered_and_throttle_public_tracking(): void
    {
        SecurityRateLimiters::register();

        $this->assertNotNull(RateLimiter::limiter('login'));
        $this->assertNotNull(RateLimiter::limiter('register'));
        $this->assertNotNull(RateLimiter::limiter('checkout'));
        $this->assertNotNull(RateLimiter::limiter('public-tracking'));
        $this->assertNotNull(RateLimiter::limiter('payment-webhook'));

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $order = app(CustomerOrderService::class)->placeOrder(
            $customer,
            [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            [
                'delivery_type' => 'pickup',
                'payment_method' => 'manual_transfer',
            ]
        );

        $endpoint = "/api/v1/track/{$order->public_tracking_token}";

        for ($i = 0; $i < 60; $i++) {
            $this->getJson($endpoint)->assertOk();
        }

        $this->getJson($endpoint)->assertStatus(429);
    }

    public function test_private_file_controller_enforces_strict_authorization_for_proofs_attachments_and_pod(): void
    {
        Storage::fake('local');

        $ownerCustomer = User::factory()->create(['name' => 'Owner Customer']);
        $ownerCustomer->assignRole('customer');

        $unrelatedCustomer = User::factory()->create(['name' => 'Unrelated Customer']);
        $unrelatedCustomer->assignRole('customer');

        $qcStaff = User::factory()->create(['name' => 'QC Packing Staff']);
        $qcStaff->assignRole('qc_packing');

        $warehouseStaff = User::factory()->create(['name' => 'Warehouse Staff']);
        $warehouseStaff->assignRole('warehouse');

        $shippingStaff = User::factory()->create(['name' => 'Shipping Staff']);
        $shippingStaff->assignRole('shipping');

        $financeStaff = User::factory()->create(['name' => 'Finance Staff']);
        $financeStaff->assignRole('finance');

        $orderService = app(CustomerOrderService::class);
        $order = $orderService->placeOrder(
            $ownerCustomer,
            [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            [
                'delivery_type' => 'delivery',
                'recipient_name' => 'Owner Customer',
                'phone' => '081211112222',
                'address_line' => 'Jl. Privasi No. 1',
                'city' => 'Bandung',
                'payment_method' => 'manual_transfer',
            ]
        );

        $payment = $orderService->uploadPaymentProof(
            $order,
            UploadedFile::fake()->image('bukti-transfer.png'),
            $ownerCustomer
        );
        $this->assertInstanceOf(OrderPayment::class, $payment);

        $order = $orderService->verifyPayment($order, $financeStaff, true, 'Valid');
        $task = OrderTask::where('order_id', $order->id)->firstOrFail();

        $attachmentPath = Storage::disk('local')->putFile(
            'task-attachments',
            UploadedFile::fake()->image('qc-note.png')
        );

        $attachment = TaskAttachment::create([
            'order_task_id' => $task->id,
            'uploaded_by' => $qcStaff->id,
            'disk' => 'local',
            'path' => $attachmentPath,
            'original_name' => 'qc-note.png',
            'mime_type' => 'image/png',
            'size_bytes' => 128,
        ]);

        $trackingService = app(ShippingTrackingService::class);
        $shipment = $trackingService->dispatchShipment($order, [
            'carrier' => 'JNE',
            'service' => 'REG',
            'tracking_number' => 'JNE-PRIV-001',
        ], $shippingStaff);

        $shipment = $trackingService->confirmDelivery(
            shipment: $shipment,
            actor: $ownerCustomer,
            source: 'customer',
            proof: UploadedFile::fake()->image('pod.png'),
            notes: 'Diterima aman'
        );
        $this->assertInstanceOf(Shipment::class, $shipment);

        // Payment proof access
        $this->actingAs($unrelatedCustomer)->get("/files/payment-proof/{$payment->id}")->assertForbidden();
        $this->actingAs($ownerCustomer)->get("/files/payment-proof/{$payment->id}")->assertOk();
        $this->actingAs($financeStaff)->get("/files/payment-proof/{$payment->id}")->assertOk();

        // Internal task attachment access (never accessible to customers, even order owner)
        $this->actingAs($unrelatedCustomer)->get("/files/task-attachment/{$attachment->id}")->assertForbidden();
        $this->actingAs($ownerCustomer)->get("/files/task-attachment/{$attachment->id}")->assertForbidden();
        $this->actingAs($qcStaff)->get("/files/task-attachment/{$attachment->id}")->assertOk();
        $this->actingAs($warehouseStaff)->get("/files/task-attachment/{$attachment->id}")->assertOk();

        // Shipment Proof of Delivery access
        $this->actingAs($unrelatedCustomer)->get("/files/shipment-pod/{$shipment->id}")->assertForbidden();
        $this->actingAs($ownerCustomer)->get("/files/shipment-pod/{$shipment->id}")->assertOk();
        $this->actingAs($shippingStaff)->get("/files/shipment-pod/{$shipment->id}")->assertOk();
    }

    public function test_zero_purple_audit_across_all_phase_blade_views(): void
    {
        $directories = [
            'inventory',
            'pos',
            'shop',
            'my-orders',
            'orders',
            'work',
            'shipping',
            'tracking',
            'procurement',
            'finance',
            'reports',
        ];

        $forbiddenTokens = [
            '#8b5cf6',
            '#7c3aed',
            '#9333ea',
            'purple-',
            'violet-',
            'indigo-',
        ];

        $scannedFiles = 0;
        $violations = [];

        foreach ($directories as $dir) {
            $path = resource_path("views/{$dir}");
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $scannedFiles++;
                $contents = strtolower((string) file_get_contents($file->getPathname()));

                foreach ($forbiddenTokens as $token) {
                    if (str_contains($contents, $token)) {
                        $violations[] = "{$file->getPathname()} contains forbidden token '{$token}'";
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $scannedFiles, 'Expected Blade views to be scanned for zero-purple audit.');
        $this->assertSame([], $violations, implode("\n", $violations));
    }

    public function test_webhook_replay_and_payload_tampering_are_handled_by_idempotency_service(): void
    {
        $idempotency = app(IdempotencyService::class);
        $executions = 0;

        $payload = [
            'order_id' => 'INV-2026-0001',
            'reference' => 'PK-998877',
            'status' => 'paid',
            'amount' => 150000,
        ];

        $firstResponse = $idempotency->execute(
            key: 'webhook-pakasir-PK-998877',
            route: 'payments.webhook',
            payload: $payload,
            callback: function () use (&$executions): array {
                $executions++;

                return [
                    'success' => true,
                    'message' => 'Webhook processed.',
                ];
            }
        );

        $this->assertSame(1, $executions);
        $this->assertTrue($firstResponse['success']);

        // Exact replay returns stored response without re-running callback
        $replayResponse = $idempotency->execute(
            key: 'webhook-pakasir-PK-998877',
            route: 'payments.webhook',
            payload: $payload,
            callback: function () use (&$executions): array {
                $executions++;

                return [
                    'success' => false,
                    'message' => 'Should not execute again.',
                ];
            }
        );

        $this->assertSame(1, $executions);
        $this->assertSame($firstResponse, $replayResponse);

        // Same key with tampered payload must fail validation
        $this->expectException(ValidationException::class);

        $idempotency->execute(
            key: 'webhook-pakasir-PK-998877',
            route: 'payments.webhook',
            payload: array_merge($payload, ['amount' => 1]),
            callback: fn (): array => ['success' => true]
        );
    }
}
