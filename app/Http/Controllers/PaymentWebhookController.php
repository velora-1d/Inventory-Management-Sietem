<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Enums\SaleStatus;
use App\Enums\PaymentMethod;
use App\Services\SaleService;
use App\Services\PakasirService;
use App\Http\Requests\PakasirWebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected SaleService $saleService,
        protected PakasirService $pakasirService,
    ) {
    }

    public function handlePakasir(PakasirWebhookRequest $request): JsonResponse
    {
        $payload = $request->validated();

        Log::info('Pakasir webhook received', [
            'order_id' => $payload['order_id'],
            'status'   => $payload['status'],
            'amount'   => $payload['amount'],
        ]);

        $sale = Sale::where('invoice_number', $payload['order_id'])->first();

        if (!$sale) {
            Log::error('Sale not found for Pakasir webhook', ['order_id' => $payload['order_id']]);
            return response()->json([
                'success' => false,
                'message' => 'Sale not found.',
            ], 404);
        }

        // Verify the transaction directly with Pakasir API for security
        $expectedAmount = (int) $sale->total;
        $response = $this->pakasirService->getTransactionDetail($sale->invoice_number, $expectedAmount);
        $transaction = $response['transaction'] ?? null;

        if (!$transaction) {
            Log::error('Pakasir transaction details not found via API', ['order_id' => $sale->invoice_number]);
            return response()->json([
                'success' => false,
                'message' => 'Transaction detail not found.',
            ], 400);
        }

        // Verify project slug
        if (($transaction['project'] ?? null) !== config('services.pakasir.slug')) {
            Log::error('Pakasir project mismatch', [
                'expected' => config('services.pakasir.slug'),
                'received' => $transaction['project'] ?? null
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Project mismatch.',
            ], 400);
        }

        // Verify amount
        if ((int) ($transaction['amount'] ?? 0) !== $expectedAmount) {
            Log::error('Pakasir transaction amount mismatch', [
                'expected' => $expectedAmount,
                'received' => $transaction['amount'] ?? null
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Amount mismatch.',
            ], 400);
        }

        // If the transaction is completed on Pakasir's end
        if (($transaction['status'] ?? null) === 'completed') {
            if ($sale->status === SaleStatus::COMPLETED) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale is already completed.',
                ]);
            }

            if ($sale->status === SaleStatus::PENDING) {
                try {
                    // Update payment method to the dynamic method used (e.g. qris, etc.)
                    $methodName = strtolower($transaction['payment_method'] ?? 'qris');
                    $resolvedMethod = PaymentMethod::tryFrom($methodName) ?? PaymentMethod::QRIS;

                    DB::transaction(function () use ($sale, $resolvedMethod) {
                        $sale->update([
                            'payment_method' => $resolvedMethod,
                            'cash_received' => $sale->total,
                            'change' => 0,
                        ]);

                        $this->saleService->completeSale($sale);
                    });

                    Log::info('Sale completed successfully via Pakasir Webhook', ['invoice_number' => $sale->invoice_number]);
                } catch (\Exception $e) {
                    Log::error('Failed to complete sale via Pakasir Webhook', [
                        'invoice_number' => $sale->invoice_number,
                        'error' => $e->getMessage()
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
        ]);
    }
}
