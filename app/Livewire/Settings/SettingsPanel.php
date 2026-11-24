<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Payments\PaymentGatewayManager;
use App\Services\AuditService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsPanel extends Component
{
    use WithFileUploads;

    public string $activeTab = 'branding';

    // Tab 1: Toko & Branding
    public string $brand_name = '';
    public string $brand_subtitle = '';
    public string $brand_short_code = '';
    public string $brand_whatsapp_admin = '';
    public string $brand_logo = '';
    public $logoUpload = null;
    public string $store_name = '';
    public string $store_phone = '';
    public string $store_address = '';

    // Tab 2: Pembayaran & Rekening Bank
    public string $payment_gateway = 'manual';
    /** @var array<int, string> */
    public array $gatewayMethods = [];
    public string $payment_bank_name = '';
    public string $payment_bank_account_number = '';
    public string $payment_bank_account_holder = '';

    // Gateway-specific credentials
    public string $payment_verapay_base_url = '';
    public string $payment_verapay_slug = '';
    public string $payment_verapay_api_key = '';
    public string $payment_pakasir_base_url = '';
    public string $payment_pakasir_slug = '';
    public string $payment_pakasir_api_key = '';
    public string $payment_midtrans_server_key = '';
    public string $payment_midtrans_client_key = '';
    public string $payment_midtrans_is_production = 'false';
    public string $payment_xendit_secret_key = '';
    public string $payment_xendit_callback_token = '';
    public string $payment_custom_checkout_url = '';
    public string $payment_custom_status_url = '';
    public string $payment_custom_api_key = '';
    public string $payment_custom_webhook_secret = '';

    // Tab 3: Mata Uang & Akuntansi
    public string $currency_symbol = 'Rp';
    public string $currency_position = 'left';
    public string $currency_fraction_digits = '0';
    public string $currency_thousand_separator = '.';
    public string $currency_decimal_separator = ',';
    public string $opening_balance_date = '';
    public string $opening_balance_amount = '0';

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['branding', 'payment', 'currency', 'advanced'], true)
            ? $tab
            : 'branding';
    }

    public function saveBranding(AuditService $auditService): void
    {
        $this->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'brand_subtitle' => ['nullable', 'string', 'max:120'],
            'brand_short_code' => ['nullable', 'string', 'max:10'],
            'brand_whatsapp_admin' => ['nullable', 'string', 'max:30'],
            'store_name' => ['nullable', 'string', 'max:120'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'logoUpload' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        if ($this->logoUpload) {
            $oldLogo = Setting::get('brand_logo');
            if ($oldLogo && !str_starts_with($oldLogo, 'http') && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $this->brand_logo = $this->logoUpload->store('branding', 'public');
            $this->reset('logoUpload');
        }

        $this->persistKeys([
            'brand_name' => $this->brand_name,
            'brand_subtitle' => $this->brand_subtitle,
            'brand_short_code' => $this->brand_short_code,
            'brand_whatsapp_admin' => $this->brand_whatsapp_admin,
            'brand_logo' => $this->brand_logo,
            'store_name' => $this->store_name,
            'store_phone' => $this->store_phone,
            'store_address' => $this->store_address,
        ], $auditService, 'Updated branding and store settings');

        $this->dispatch('toast', message: 'Pengaturan Toko & Branding berhasil disimpan.', type: 'success');
    }

    public function resetLogo(AuditService $auditService): void
    {
        $oldValue = Setting::get('brand_logo');
        if ($oldValue && !str_starts_with($oldValue, 'http') && Storage::disk('public')->exists($oldValue)) {
            Storage::disk('public')->delete($oldValue);
        }

        Setting::set('brand_logo', '');
        $this->brand_logo = '';
        $this->reset('logoUpload');

        $auditService->log(
            action: 'setting.updated',
            before: ['key' => 'brand_logo', 'value' => $oldValue],
            after: ['key' => 'brand_logo', 'value' => ''],
            reason: 'Reset brand logo to default SVG'
        );

        $this->dispatch('toast', message: 'Logo berhasil dikembalikan ke default.', type: 'success');
    }

    public function savePayment(AuditService $auditService): void
    {
        $this->validate([
            'payment_gateway' => ['required', 'in:manual,verapay,pakasir,midtrans,xendit,custom'],
            'payment_bank_name' => ['nullable', 'string', 'max:100'],
            'payment_bank_account_number' => ['nullable', 'string', 'max:60'],
            'payment_bank_account_holder' => ['nullable', 'string', 'max:120'],
            'payment_verapay_base_url' => ['nullable', 'string', 'max:255'],
            'payment_verapay_slug' => ['nullable', 'string', 'max:100'],
            'payment_verapay_api_key' => ['nullable', 'string', 'max:255'],
            'payment_pakasir_base_url' => ['nullable', 'string', 'max:255'],
            'payment_pakasir_slug' => ['nullable', 'string', 'max:100'],
            'payment_pakasir_api_key' => ['nullable', 'string', 'max:255'],
            'payment_midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'payment_midtrans_client_key' => ['nullable', 'string', 'max:255'],
            'payment_midtrans_is_production' => ['required', 'in:true,false'],
            'payment_xendit_secret_key' => ['nullable', 'string', 'max:255'],
            'payment_xendit_callback_token' => ['nullable', 'string', 'max:255'],
            'payment_custom_checkout_url' => ['nullable', 'string', 'max:500'],
            'payment_custom_status_url' => ['nullable', 'string', 'max:500'],
            'payment_custom_api_key' => ['nullable', 'string', 'max:255'],
            'payment_custom_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $methods = implode(',', array_values(array_filter(array_unique($this->gatewayMethods))));

        $this->persistKeys([
            'payment_gateway' => $this->payment_gateway,
            'payment_gateway_methods' => $methods,
            'payment_bank_name' => $this->payment_bank_name,
            'payment_bank_account_number' => $this->payment_bank_account_number,
            'payment_bank_account_holder' => $this->payment_bank_account_holder,
            'payment_verapay_base_url' => $this->payment_verapay_base_url,
            'payment_verapay_slug' => $this->payment_verapay_slug,
            'payment_verapay_api_key' => $this->payment_verapay_api_key,
            'payment_pakasir_base_url' => $this->payment_pakasir_base_url,
            'payment_pakasir_slug' => $this->payment_pakasir_slug,
            'payment_pakasir_api_key' => $this->payment_pakasir_api_key,
            'payment_midtrans_server_key' => $this->payment_midtrans_server_key,
            'payment_midtrans_client_key' => $this->payment_midtrans_client_key,
            'payment_midtrans_is_production' => $this->payment_midtrans_is_production,
            'payment_xendit_secret_key' => $this->payment_xendit_secret_key,
            'payment_xendit_callback_token' => $this->payment_xendit_callback_token,
            'payment_custom_checkout_url' => $this->payment_custom_checkout_url,
            'payment_custom_status_url' => $this->payment_custom_status_url,
            'payment_custom_api_key' => $this->payment_custom_api_key,
            'payment_custom_webhook_secret' => $this->payment_custom_webhook_secret,
        ], $auditService, 'Updated payment gateway and bank transfer settings');

        $this->dispatch('toast', message: 'Pengaturan Pembayaran & Rekening berhasil disimpan.', type: 'success');
    }

    public function saveCurrency(AuditService $auditService): void
    {
        $this->validate([
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_position' => ['required', 'in:left,right'],
            'currency_fraction_digits' => ['required', 'in:0,1,2'],
            'currency_thousand_separator' => ['required', 'string', 'max:3'],
            'currency_decimal_separator' => ['required', 'string', 'max:3'],
            'opening_balance_date' => ['nullable', 'date'],
            'opening_balance_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->persistKeys([
            'currency_symbol' => $this->currency_symbol,
            'currency_position' => $this->currency_position,
            'currency_fraction_digits' => $this->currency_fraction_digits,
            'currency_thousand_separator' => $this->currency_thousand_separator,
            'currency_decimal_separator' => $this->currency_decimal_separator,
            'opening_balance_date' => $this->opening_balance_date,
            'opening_balance_amount' => $this->opening_balance_amount,
        ], $auditService, 'Updated currency and opening balance settings');

        $this->dispatch('toast', message: 'Pengaturan Mata Uang & Akuntansi berhasil disimpan.', type: 'success');
    }

    public function getPreviewCurrencyProperty(): string
    {
        $decimals = max(0, min(2, (int) $this->currency_fraction_digits));
        $formatted = number_format(1250000, $decimals, $this->currency_decimal_separator ?: ',', $this->currency_thousand_separator ?: '.');

        return $this->currency_position === 'right'
            ? "{$formatted} {$this->currency_symbol}"
            : "{$this->currency_symbol} {$formatted}";
    }

    protected function loadSettings(): void
    {
        $this->brand_name = (string) Setting::get('brand_name', 'POS Skillage Edupark');
        $this->brand_subtitle = (string) Setting::get('brand_subtitle', 'Business Operations Platform');
        $this->brand_short_code = (string) Setting::get('brand_short_code', 'PSE');
        $this->brand_whatsapp_admin = (string) Setting::get('brand_whatsapp_admin', '081234567890');
        $this->brand_logo = (string) Setting::get('brand_logo', '');
        $this->store_name = (string) Setting::get('store_name', '');
        $this->store_phone = (string) Setting::get('store_phone', '');
        $this->store_address = (string) Setting::get('store_address', '');

        $this->payment_gateway = (string) Setting::get('payment_gateway', config('payment.default', 'manual'));
        $rawMethods = (string) Setting::get('payment_gateway_methods', 'qris,ewallet,virtual_account');
        $this->gatewayMethods = array_values(array_filter(array_map('trim', explode(',', $rawMethods))));

        $this->payment_bank_name = (string) Setting::get('payment_bank_name', '');
        $this->payment_bank_account_number = (string) Setting::get('payment_bank_account_number', '');
        $this->payment_bank_account_holder = (string) Setting::get('payment_bank_account_holder', '');

        $this->payment_verapay_base_url = (string) Setting::get('payment_verapay_base_url', '');
        $this->payment_verapay_slug = (string) Setting::get('payment_verapay_slug', '');
        $this->payment_verapay_api_key = (string) Setting::get('payment_verapay_api_key', '');
        $this->payment_pakasir_base_url = (string) Setting::get('payment_pakasir_base_url', 'https://app.pakasir.com');
        $this->payment_pakasir_slug = (string) Setting::get('payment_pakasir_slug', '');
        $this->payment_pakasir_api_key = (string) Setting::get('payment_pakasir_api_key', '');
        $this->payment_midtrans_server_key = (string) Setting::get('payment_midtrans_server_key', '');
        $this->payment_midtrans_client_key = (string) Setting::get('payment_midtrans_client_key', '');
        $this->payment_midtrans_is_production = (string) Setting::get('payment_midtrans_is_production', 'false');
        $this->payment_xendit_secret_key = (string) Setting::get('payment_xendit_secret_key', '');
        $this->payment_xendit_callback_token = (string) Setting::get('payment_xendit_callback_token', '');
        $this->payment_custom_checkout_url = (string) Setting::get('payment_custom_checkout_url', '');
        $this->payment_custom_status_url = (string) Setting::get('payment_custom_status_url', '');
        $this->payment_custom_api_key = (string) Setting::get('payment_custom_api_key', '');
        $this->payment_custom_webhook_secret = (string) Setting::get('payment_custom_webhook_secret', '');

        $this->currency_symbol = (string) Setting::get('currency_symbol', 'Rp');
        $this->currency_position = (string) Setting::get('currency_position', 'left');
        $this->currency_fraction_digits = (string) Setting::get('currency_fraction_digits', '0');
        $this->currency_thousand_separator = (string) Setting::get('currency_thousand_separator', '.');
        $this->currency_decimal_separator = (string) Setting::get('currency_decimal_separator', ',');
        $this->opening_balance_date = (string) Setting::get('opening_balance_date', '');
        $this->opening_balance_amount = (string) Setting::get('opening_balance_amount', '0');
    }

    /**
     * @param array<string, mixed> $pairs
     */
    protected function persistKeys(array $pairs, AuditService $auditService, string $reason): void
    {
        $before = [];
        $after = [];

        foreach ($pairs as $key => $value) {
            $old = Setting::get($key);
            $new = $value === null ? '' : (string) $value;
            if ((string) $old === $new) {
                continue;
            }

            $isSecret = Str::endsWith($key, ['_key', '_secret', '_token']);
            $before[$key] = $isSecret && $old ? '[REDACTED]' : $old;
            $after[$key] = $isSecret && $new ? '[REDACTED]' : $new;

            Setting::set($key, $new);
        }

        if (!empty($after)) {
            $auditService->log(
                action: 'setting.batch_updated',
                before: $before,
                after: $after,
                reason: $reason
            );
        }
    }

    public function render(PaymentGatewayManager $paymentManager)
    {
        return view('livewire.settings.settings-panel', [
            'gateways' => $paymentManager->availableGateways(),
        ]);
    }
}
