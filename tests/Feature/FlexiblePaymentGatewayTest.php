<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlexiblePaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('cashier');
        $category = Category::create([
            'name' => 'Bahan Bangunan',
            'slug' => 'bahan-bangunan',
        ]);
        $unit = Unit::create([
            'name' => 'Pieces',
            'symbol' => 'pcs',
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_price' => 10000,
            'selling_price' => 25000,
            'quantity' => 50,
            'is_active' => true,
        ]);
    }

    public function test_manual_gateway_processes_qris_sale_without_external_credentials(): void
    {
        Setting::set('payment_gateway', 'manual');

        $response = $this->actingAs($this->user)->postJson(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::QRIS->value,
            'status' => SaleStatus::COMPLETED->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 25000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('checkout_url', null)
            ->assertJsonPath('payment_gateway', 'manual');

        $sale = Sale::firstOrFail();
        $this->assertSame(SaleStatus::COMPLETED, $sale->status);
        $this->assertSame('manual', $sale->payment_gateway);
        $this->assertNull($sale->payment_checkout_url);
    }

    public function test_pakasir_gateway_creates_checkout_and_processes_callback(): void
    {
        Setting::set('payment_gateway', 'pakasir');
        Setting::set('payment_pakasir_slug', 'toko-test');
        Setting::set('payment_pakasir_api_key', 'pakasir-secret-key');
        Setting::set('payment_pakasir_base_url', 'https://app.pakasir.com');

        $response = $this->actingAs($this->user)->postJson(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::QRIS->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 25000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('payment_gateway', 'pakasir');

        $checkoutUrl = $response->json('checkout_url');
        $this->assertNotNull($checkoutUrl);
        $this->assertStringContainsString('https://app.pakasir.com/pay/toko-test/50000', $checkoutUrl);

        $sale = Sale::firstOrFail();
        $this->assertSame(SaleStatus::PENDING, $sale->status);

        Http::fake([
            'https://app.pakasir.com/api/transactiondetail*' => Http::response([
                'transaction' => [
                    'project' => 'toko-test',
                    'order_id' => $sale->invoice_number,
                    'amount' => 50000,
                    'status' => 'completed',
                    'payment_method' => 'qris',
                ],
            ], 200),
        ]);

        $webhook = $this->postJson('/api/v1/callback/pakasir', [
            'amount' => 50000,
            'order_id' => $sale->invoice_number,
            'project' => 'toko-test',
            'status' => 'completed',
            'payment_method' => 'qris',
        ]);

        $webhook->assertOk()->assertJsonPath('success', true);

        $sale->refresh();
        $this->assertSame(SaleStatus::COMPLETED, $sale->status);
    }

    public function test_midtrans_gateway_creates_snap_checkout_and_verifies_webhook_signature(): void
    {
        Setting::set('payment_gateway', 'midtrans');
        Setting::set('payment_midtrans_server_key', 'SB-Mid-server-test123');
        Setting::set('payment_midtrans_is_production', 'false');

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
            ], 201),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::QRIS->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 25000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('payment_gateway', 'midtrans')
            ->assertJsonPath('checkout_url', 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123');

        $sale = Sale::firstOrFail();

        $invalidWebhook = $this->postJson('/api/v1/webhooks/payment/midtrans', [
            'order_id' => $sale->invoice_number,
            'status_code' => '200',
            'gross_amount' => '25000.00',
            'signature_key' => 'invalid-signature',
            'transaction_status' => 'settlement',
        ]);
        $invalidWebhook->assertStatus(401);

        $signature = hash('sha512', $sale->invoice_number . '200' . '25000.00' . 'SB-Mid-server-test123');
        $validWebhook = $this->postJson('/api/v1/webhooks/payment/midtrans', [
            'order_id' => $sale->invoice_number,
            'status_code' => '200',
            'gross_amount' => '25000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'transaction_id' => 'mid-tx-999',
            'payment_type' => 'qris',
        ]);

        $validWebhook->assertOk()->assertJsonPath('success', true);
        $this->assertSame(SaleStatus::COMPLETED, $sale->fresh()->status);
    }

    public function test_xendit_gateway_creates_invoice_and_verifies_callback_token(): void
    {
        Setting::set('payment_gateway', 'xendit');
        Setting::set('payment_xendit_secret_key', 'xnd_development_secret');
        Setting::set('payment_xendit_callback_token', 'xendit-cb-token');

        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'xnd_inv_001',
                'external_id' => 'INV-TEST',
                'invoice_url' => 'https://checkout.xendit.co/web/xnd_inv_001',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::EWALLET->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 25000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('payment_gateway', 'xendit')
            ->assertJsonPath('checkout_url', 'https://checkout.xendit.co/web/xnd_inv_001');

        $sale = Sale::firstOrFail();

        $invalid = $this->withHeaders(['x-callback-token' => 'wrong'])
            ->postJson('/api/v1/webhooks/payment/xendit', [
                'id' => 'xnd_inv_001',
                'external_id' => $sale->invoice_number,
                'status' => 'PAID',
                'amount' => 50000,
            ]);
        $invalid->assertStatus(401);

        $valid = $this->withHeaders(['x-callback-token' => 'xendit-cb-token'])
            ->postJson('/api/v1/webhooks/payment/xendit', [
                'id' => 'xnd_inv_001',
                'external_id' => $sale->invoice_number,
                'status' => 'PAID',
                'amount' => 50000,
                'payment_method' => 'EWALLET',
            ]);
        $valid->assertOk()->assertJsonPath('success', true);
        $this->assertSame(SaleStatus::COMPLETED, $sale->fresh()->status);
    }

    public function test_custom_gateway_builds_templated_url_and_verifies_webhook_secret(): void
    {
        Setting::set('payment_gateway', 'custom');
        Setting::set('payment_custom_checkout_url', 'https://pay.example.test/checkout?order={order_id}&amount={amount}&method={method}');
        Setting::set('payment_custom_webhook_secret', 'custom-secret-123');

        $response = $this->actingAs($this->user)->postJson(route('sales.store'), [
            'sale_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::VIRTUAL_ACCOUNT->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 25000,
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('payment_gateway', 'custom');

        $sale = Sale::firstOrFail();
        $this->assertStringContainsString(
            'https://pay.example.test/checkout?order=' . urlencode($sale->invoice_number) . '&amount=25000&method=virtual_account',
            (string) $response->json('checkout_url')
        );

        $webhook = $this->withHeaders(['X-Webhook-Secret' => 'custom-secret-123'])
            ->postJson('/api/v1/webhooks/payment/custom', [
                'order_id' => $sale->invoice_number,
                'status' => 'paid',
                'amount' => 25000,
                'reference' => 'CUSTOM-REF-01',
            ]);

        $webhook->assertOk()->assertJsonPath('success', true);
        $this->assertSame(SaleStatus::COMPLETED, $sale->fresh()->status);
    }
}
