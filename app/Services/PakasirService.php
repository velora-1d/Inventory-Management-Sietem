<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Illuminate\Validation\ValidationException;

class PakasirService
{
    public function buildGatewayCheckoutUrl(string $orderId, int $amount, string $redirectUrl, string $method): string
    {
        $config = $this->getConfig();

        $params = [
            'order_id' => $orderId,
            'redirect' => $redirectUrl,
        ];

        if ($method === 'qris') {
            $params['qris_only'] = 1;
        }

        return sprintf(
            '%s/pay/%s/%d?%s',
            rtrim($config['base_url'], '/'),
            $config['slug'],
            $amount,
            http_build_query($params),
        );
    }

    public function getTransactionDetail(string $orderId, int $amount): array
    {
        $config = $this->getConfig();

        return $this->sendJsonRequest('GET', '/api/transactiondetail', [
            'project' => $config['slug'],
            'amount' => $amount,
            'order_id' => $orderId,
            'api_key' => $config['api_key'],
        ]);
    }

    public function cancelTransaction(string $orderId, int $amount): array
    {
        $config = $this->getConfig();

        return $this->sendJsonRequest('POST', '/api/transactioncancel', [
            'project' => $config['slug'],
            'amount' => $amount,
            'order_id' => $orderId,
            'api_key' => $config['api_key'],
        ]);
    }

    protected function sendJsonRequest(string $method, string $path, array $payload): array
    {
        $config = $this->getConfig();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->send($method, rtrim($config['base_url'], '/').$path, [
                    $method === 'GET' ? 'query' : 'json' => $payload,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal menghubungi gateway Pakasir.', previous: $exception);
        }

        return $response->json() ?? [];
    }

    protected function getConfig(): array
    {
        $config = config('services.pakasir');

        if (!$config['api_key'] || !$config['slug'] || !$config['base_url']) {
            throw ValidationException::withMessages([
                'payment_gateway' => 'Konfigurasi Pakasir belum lengkap di .env.',
            ]);
        }

        return $config;
    }
}
