<?php

namespace App\Http\Controllers;

use App\DTOs\SaleData;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\SaleException;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Payments\PaymentGatewayManager;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    public function index()
    {
        return view('sales.index');
    }

    public function create(PaymentGatewayManager $paymentManager)
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $products = \App\Models\Product::with(['unit', 'category'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $activeDriver = $paymentManager->activeDriver();
        $activeGatewayKey = $activeDriver->key();
        $activeGatewayLabel = $activeDriver->label();
        $gatewayMethods = $activeGatewayKey === 'manual'
            ? []
            : array_values(array_intersect(
                $paymentManager->gatewayHandledMethods(),
                $activeDriver->supportedMethods()
            ));
        $availableGateways = $paymentManager->availableGateways();
        $paymentMethods = array_map(fn (PaymentMethod $method) => [
            'value' => $method->value,
            'label' => $method->label(),
        ], PaymentMethod::cases());

        return view('sales.create', compact(
            'categories',
            'products',
            'activeGatewayKey',
            'activeGatewayLabel',
            'gatewayMethods',
            'availableGateways',
            'paymentMethods',
        ));
    }

    public function store(StoreSaleRequest $request, SaleService $saleService, PaymentGatewayManager $paymentManager)
    {
        try {
            $validated = $request->validated();
            $validated['created_by'] = Auth::id();

            $paymentMethod = PaymentMethod::from($validated['payment_method']);
            $requestedGateway = $validated['payment_gateway'] ?? null;
            $useOnlineGateway = $paymentManager->shouldUseGateway($paymentMethod, $requestedGateway);

            if ($useOnlineGateway) {
                $validated['status'] = SaleStatus::PENDING->value;
            }

            $saleData = SaleData::fromArray($validated);
            $sale = $saleService->createSale($saleData);

            $checkoutUrl = null;
            if ($useOnlineGateway) {
                $gateway = $paymentManager->driver($requestedGateway);
                $checkoutResult = $gateway->createCheckout(
                    $sale,
                    route('sales.show', $sale->id),
                    ['method' => $sale->payment_method->value]
                );

                $checkoutUrl = $checkoutResult->checkoutUrl;

                $sale->update([
                    'payment_gateway' => $checkoutResult->gateway,
                    'payment_reference' => $checkoutResult->reference,
                    'payment_checkout_url' => $checkoutUrl,
                    'payment_meta' => $checkoutResult->meta,
                ]);
            } else {
                $sale->update([
                    'payment_gateway' => 'manual',
                    'payment_reference' => $sale->invoice_number,
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale created successfully',
                    'data' => $sale->fresh(),
                    'checkout_url' => $checkoutUrl,
                    'payment_gateway' => $sale->payment_gateway,
                    'print_url' => route('sales.print', $sale->id),
                    'redirect' => $checkoutUrl ?: route('sales.create'),
                ], 201);
            }

            if ($checkoutUrl) {
                return redirect()->away($checkoutUrl);
            }

            return redirect()->route('sales.create')
                ->with('success', 'Sale created successfully.');

        } catch (SaleException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 400);
            }
            return back()->with('error', $e->getMessage())->withInput();

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 400);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product.unit', 'customer', 'creator']);
        return view('sales.show', compact('sale'));
    }

    public function destroy(Request $request, Sale $sale, SaleService $saleService, PaymentGatewayManager $paymentManager)
    {
        try {
            if ($sale->status === SaleStatus::PENDING && $sale->payment_gateway && $sale->payment_gateway !== 'manual') {
                try {
                    $paymentManager->driver($sale->payment_gateway)->cancelTransaction($sale);
                } catch (\Throwable $gatewayException) {
                    Log::warning('Remote payment cancellation failed while cancelling local sale', [
                        'sale_id' => $sale->id,
                        'gateway' => $sale->payment_gateway,
                        'error' => $gatewayException->getMessage(),
                    ]);
                }
            }

            $reason = $request->input('reason');
            $saleService->cancelSale($sale, $reason);
            return redirect()->route('sales.index')->with('success', 'Sale cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function print(Sale $sale)
    {
        $sale->load(['items.product.unit', 'customer', 'creator']);
        return view('sales.print', compact('sale'));
    }

    public function checkStatus(Request $request, Sale $sale, PaymentGatewayManager $paymentManager)
    {
        if (
            $request->boolean('sync')
            && $sale->status === SaleStatus::PENDING
            && $sale->payment_gateway
            && $sale->payment_gateway !== 'manual'
        ) {
            try {
                $statusResult = $paymentManager->driver($sale->payment_gateway)->getTransactionStatus($sale);
                if ($statusResult->status === 'paid') {
                    app(SaleService::class)->completeSale($sale, [
                        'cash_received' => $sale->total,
                        'change' => 0,
                    ]);
                    $sale->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning('Payment status sync failed', [
                    'sale_id' => $sale->id,
                    'gateway' => $sale->payment_gateway,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => $sale->status->value,
            'payment_gateway' => $sale->payment_gateway,
            'print_url' => route('sales.print', $sale->id),
        ]);
    }

    public function restore(Sale $sale, SaleService $saleService)
    {
        try {
            $saleService->restoreSale($sale);
            return redirect()->back()->with('success', 'Sale restored to Pending.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request, Sale $sale, SaleService $saleService)
    {
        try {
            $paymentData = $request->only(['cash_received', 'change']);

            $saleService->completeSale($sale, $paymentData);

            return redirect()->back()->with('success', 'Sale marked as completed.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
