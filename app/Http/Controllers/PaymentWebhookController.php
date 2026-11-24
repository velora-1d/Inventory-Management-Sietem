<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Sale;
use App\Payments\PaymentGatewayManager;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected SaleService $saleService,
        protected PaymentGatewayManager $paymentManager,
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        try {
            $driver = $this->paymentManager->driver($gateway);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        try {
            $webhook = $driver->parseWebhook($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::warning('Payment webhook verification/parsing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            $status = str_contains(strtolower($e->getMessage()), 'invalid') ? 401 : 400;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }

        Log::info('Payment webhook received', [
            'gateway' => $webhook->gateway,
            'order_id' => $webhook->orderId,
            'reference' => $webhook->reference,
            'status' => $webhook->status,
            'amount' => $webhook->amount,
        ]);

        $sale = Sale::where('invoice_number', $webhook->orderId)
            ->orWhere('payment_reference', $webhook->reference)
            ->first();

        if (!$sale) {
            $order = Order::where('order_number', $webhook->orderId)->first();
            if ($order) {
                return $this->handleOrderWebhook($order, $webhook);
            }

            Log::error('Sale not found for payment webhook', [
                'gateway' => $webhook->gateway,
                'order_id' => $webhook->orderId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sale not found.',
            ], 404);
        }

        $expectedAmount = (int) $sale->total;
        if ($webhook->amount !== $expectedAmount) {
            Log::error('Payment webhook amount mismatch', [
                'gateway' => $webhook->gateway,
                'order_id' => $sale->invoice_number,
                'expected' => $expectedAmount,
                'received' => $webhook->amount,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Amount mismatch.',
            ], 400);
        }

        if ($webhook->status === 'paid') {
            if ($sale->status === SaleStatus::COMPLETED) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale is already completed.',
                ]);
            }

            if ($sale->status === SaleStatus::PENDING) {
                try {
                    $resolvedMethod = $webhook->paymentMethod
                        ? (PaymentMethod::tryFrom($webhook->paymentMethod) ?? $sale->payment_method)
                        : $sale->payment_method;

                    DB::transaction(function () use ($sale, $resolvedMethod, $webhook) {
                        $sale->update([
                            'payment_method' => $resolvedMethod,
                            'payment_gateway' => $webhook->gateway,
                            'payment_reference' => $webhook->reference,
                            'payment_meta' => array_merge($sale->payment_meta ?? [], [
                                'webhook' => $webhook->raw,
                            ]),
                            'cash_received' => $sale->total,
                            'change' => 0,
                        ]);

                        $this->saleService->completeSale($sale);
                    });

                    Log::info('Sale completed successfully via payment webhook', [
                        'gateway' => $webhook->gateway,
                        'invoice_number' => $sale->invoice_number,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to complete sale via payment webhook', [
                        'gateway' => $webhook->gateway,
                        'invoice_number' => $sale->invoice_number,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to complete sale: ' . $e->getMessage(),
                    ], 500);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed.',
            'gateway' => $webhook->gateway,
            'status' => $webhook->status,
        ]);
    }

    public function handlePakasir(Request $request): JsonResponse
    {
        return $this->handle($request, 'pakasir');
    }

    protected function handleOrderWebhook(Order $order, \App\Payments\DTOs\PaymentWebhookPayload $webhook): JsonResponse
    {
        $expectedAmount = (int) $order->total;
        if ($webhook->amount !== $expectedAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Amount mismatch.',
            ], 422);
        }

        if ($webhook->status === 'paid' && $order->payment_status !== 'paid') {
            DB::transaction(function () use ($order, $webhook): void {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($locked->payment_status === 'paid') {
                    return;
                }

                $fromStatus = $locked->status;
                $locked->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                $locked->payments()->latest('id')->first()?->update([
                    'status' => 'verified',
                    'reference' => $webhook->reference,
                    'verified_at' => now(),
                ]);

                OrderStatusHistory::query()->create([
                    'order_id' => $locked->id,
                    'from_status' => $fromStatus,
                    'to_status' => 'confirmed',
                    'reason' => 'Pembayaran terverifikasi via ' . strtoupper($webhook->gateway),
                    'created_at' => now(),
                ]);

                if (class_exists(\App\Services\ShippingTrackingService::class)) {
                    app(\App\Services\ShippingTrackingService::class)->recordMilestone(
                        $locked,
                        'payment_confirmed',
                        'Pembayaran terkonfirmasi',
                        'Pembayaran terverifikasi otomatis oleh ' . strtoupper($webhook->gateway) . '.',
                        true
                    );
                }
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Order webhook processed successfully.',
            'gateway' => $webhook->gateway,
            'status' => $webhook->status,
        ]);
    }
}
