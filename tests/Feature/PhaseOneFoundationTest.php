<?php

namespace Tests\Feature;

use App\Livewire\Settings\SettingForm;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\IdempotencyService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhaseOneFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    public function test_branding_settings_can_be_updated_and_reflected_in_ui_and_helpers(): void
    {
        $this->assertSame('POS Skillage Edupark', brand_name());
        $this->assertSame('Business Operations Platform', brand_subtitle());
        $this->assertSame('PSE', brand_short_code());

        Setting::set('brand_name', 'Toko Sejahtera');
        Setting::set('brand_subtitle', 'Smart Retail System');

        $this->assertSame('Toko Sejahtera', brand_name());
        $this->assertSame('Smart Retail System', brand_subtitle());

        $this->get('/login')
            ->assertOk()
            ->assertSee('Toko Sejahtera')
            ->assertSee('Smart Retail System');

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Toko Sejahtera');
    }

    public function test_setting_form_supports_logo_upload_and_redacts_secrets_in_audit_log(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(SettingForm::class)
            ->call('edit', 'brand_logo')
            ->set('logoUpload', UploadedFile::fake()->image('brand-logo.png', 120, 120))
            ->call('save')
            ->assertHasNoErrors();

        $logoPath = Setting::get('brand_logo');
        $this->assertNotEmpty($logoPath);
        Storage::disk('public')->assertExists($logoPath);
        $this->assertNotNull(brand_logo_url());

        Livewire::actingAs($admin)
            ->test(SettingForm::class)
            ->call('edit', 'brand_logo')
            ->call('resetLogo');

        $this->assertSame('', (string) Setting::get('brand_logo'));
        $this->assertNull(brand_logo_url());

        Livewire::actingAs($admin)
            ->test(SettingForm::class)
            ->call('edit', 'payment_custom_webhook_secret')
            ->set('value', 'super-secret-value-123')
            ->call('save')
            ->assertHasNoErrors();

        $audit = AuditLog::query()
            ->where('action', 'setting.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('[REDACTED]', $audit->after['value'] ?? null);
    }

    public function test_rbac_seeds_all_eleven_roles_and_enforces_route_permissions(): void
    {
        $expectedRoles = [
            'owner',
            'super_admin',
            'admin',
            'manager',
            'purchasing',
            'warehouse',
            'qc_packing',
            'cashier',
            'finance',
            'shipping',
            'customer',
        ];

        $this->assertEqualsCanonicalizing(
            $expectedRoles,
            Role::query()->pluck('name')->all()
        );

        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->get('/sales/create')->assertOk();
        $this->actingAs($cashier)->get('/sales')->assertOk();
        $this->actingAs($cashier)->get('/users')->assertForbidden();
        $this->actingAs($cashier)->get('/settings')->assertForbidden();
        $this->actingAs($cashier)->get('/audit-logs')->assertForbidden();
        $this->actingAs($cashier)->get('/finance/transactions')->assertForbidden();

        $finance = User::factory()->create();
        $finance->assignRole('finance');

        $this->actingAs($finance)->get('/finance/transactions')->assertOk();
        $this->actingAs($finance)->get('/users')->assertForbidden();
        $this->actingAs($finance)->get('/sales/create')->assertForbidden();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->get('/dashboard')->assertForbidden();
        $this->actingAs($customer)->get('/users')->assertForbidden();

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner)->get('/dashboard')->assertOk();
        $this->actingAs($owner)->get('/users')->assertOk();
        $this->actingAs($owner)->get('/settings')->assertOk();
        $this->actingAs($owner)->get('/audit-logs')->assertOk();
    }

    public function test_audit_service_and_idempotency_service_behave_deterministically(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $auditService = app(AuditService::class);
        $log = $auditService->log(
            action: 'test.action',
            entity: $admin,
            before: ['role' => 'cashier'],
            after: ['role' => 'manager'],
            reason: 'Promosi jabatan',
            actorId: $admin->id,
        );

        $this->assertSame('test.action', $log->action);
        $this->assertSame(['role' => 'manager'], $log->after);
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'reason' => 'Promosi jabatan']);

        $idempotency = app(IdempotencyService::class);
        $executions = 0;

        $first = $idempotency->execute(
            key: 'idem-pos-001',
            route: 'sales.store',
            payload: ['amount' => 50000],
            callback: function () use (&$executions) {
                $executions++;

                return ['sale_id' => 99];
            },
            actorId: $admin->id,
        );

        $this->assertSame(1, $executions);
        $this->assertSame(99, $first['sale_id']);

        $replay = $idempotency->execute(
            key: 'idem-pos-001',
            route: 'sales.store',
            payload: ['amount' => 50000],
            callback: function () use (&$executions) {
                $executions++;

                return ['sale_id' => 100];
            },
            actorId: $admin->id,
        );

        $this->assertSame(1, $executions);
        $this->assertSame(99, $replay['sale_id']);

        $this->expectException(ValidationException::class);

        $idempotency->execute(
            key: 'idem-pos-001',
            route: 'sales.store',
            payload: ['amount' => 75000],
            callback: fn () => ['sale_id' => 101],
            actorId: $admin->id,
        );
    }

    public function test_blueprint_design_tokens_remove_purple_accents(): void
    {
        $css = file_get_contents(resource_path('css/app.css')) ?: '';
        $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php')) ?: '';

        $this->assertStringContainsString('#142B4A', $css);
        $this->assertStringContainsString('#2563EB', $css);
        $this->assertStringContainsString('#0F766E', $css);

        $this->assertStringNotContainsString('#8b5cf6', strtolower($nav . $css));
        $this->assertStringNotContainsString('#7c3aed', strtolower($nav . $css));
        $this->assertStringNotContainsString('#9333ea', strtolower($nav . $css));
    }
}
