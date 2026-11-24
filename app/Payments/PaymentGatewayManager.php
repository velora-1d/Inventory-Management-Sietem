<?php

namespace App\Payments;

use App\Enums\PaymentMethod;
use App\Payments\Concerns\ReadsGatewaySettings;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Drivers\GenericWebhookGateway;
use App\Payments\Drivers\ManualGateway;
use App\Payments\Drivers\MidtransGateway;
use App\Payments\Drivers\PakasirGateway;
use App\Payments\Drivers\VeraPayGateway;
use App\Payments\Drivers\XenditGateway;
use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PaymentGatewayManager
{
    use ReadsGatewaySettings;

    /**
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $resolved = [];

    /**
     * @var array<string, Closure>
     */
    protected array $customCreators = [];

    public function __construct(
        protected Container $container
    ) {}

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $key = strtolower(trim($name ?: $this->activeDriverKey()));

        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        return $this->resolved[$key] = $this->createDriver($key);
    }

    public function activeDriverKey(): string
    {
        $default = (string) config('payment.default', 'manual');
        $configured = (string) $this->settingOrFallback('payment_gateway', $default);

        return strtolower(trim($configured ?: 'manual'));
    }

    public function activeDriver(): PaymentGatewayInterface
    {
        return $this->driver($this->activeDriverKey());
    }

    public function extend(string $key, Closure $resolver): void
    {
        $normalized = strtolower(trim($key));
        $this->customCreators[$normalized] = $resolver;
        unset($this->resolved[$normalized]);
    }

    /**
     * @return array<string, array{key: string, label: string, configured: bool, redirect: bool}>
     */
    public function availableGateways(): array
    {
        $keys = array_unique(array_merge(
            array_keys(config('payment.gateways', [])),
            array_keys($this->customCreators),
        ));

        $result = [];
        foreach ($keys as $key) {
            $gateway = $this->driver($key);
            $result[$key] = [
                'key' => $gateway->key(),
                'label' => $gateway->label(),
                'configured' => $gateway->isConfigured(),
                'redirect' => $gateway->supportsRedirectCheckout(),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function gatewayHandledMethods(): array
    {
        $fallback = implode(',', (array) config('payment.gateway_methods', ['qris', 'ewallet', 'virtual_account']));
        $raw = (string) $this->settingOrFallback('payment_gateway_methods', $fallback);

        return array_values(array_filter(array_map(
            fn (string $item) => strtolower(trim($item)),
            explode(',', $raw)
        )));
    }

    public function shouldUseGateway(PaymentMethod $method, ?string $gatewayOverride = null): bool
    {
        if ($method === PaymentMethod::CASH) {
            return false;
        }

        $key = strtolower(trim($gatewayOverride ?: $this->activeDriverKey()));
        if ($key === 'manual') {
            return false;
        }

        $driver = $this->driver($key);
        if (!$driver->supportsRedirectCheckout()) {
            return false;
        }

        return in_array($method->value, $this->gatewayHandledMethods(), true)
            && in_array($method->value, $driver->supportedMethods(), true);
    }

    protected function createDriver(string $key): PaymentGatewayInterface
    {
        if (isset($this->customCreators[$key])) {
            return ($this->customCreators[$key])($this->container);
        }

        return match ($key) {
            'manual' => $this->container->make(ManualGateway::class),
            'pakasir' => $this->container->make(PakasirGateway::class),
            'verapay' => $this->container->make(VeraPayGateway::class),
            'midtrans' => $this->container->make(MidtransGateway::class),
            'xendit' => $this->container->make(XenditGateway::class),
            'custom' => $this->container->make(GenericWebhookGateway::class),
            default => throw new InvalidArgumentException("Payment gateway [{$key}] is not supported."),
        };
    }
}
