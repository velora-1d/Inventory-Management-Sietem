<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\DTOs\SaleData;
use Illuminate\Http\Request;
use App\Services\SaleService;
use App\Exceptions\SaleException;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreSaleRequest;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Services\PakasirService;

class SalesController extends Controller
{
    public function index()
    {
        return view('sales.index');
    }

    public function create()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $products = \App\Models\Product::with(['unit', 'category'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('sales.create', compact('categories', 'products'));
    }

    public function store(StoreSaleRequest $request, SaleService $saleService)
    {
        try {
            $validated = $request->validated();
            $validated['created_by'] = Auth::id();

            // Force status to PENDING for gateway payments (QRIS)
            if ($validated['payment_method'] === PaymentMethod::QRIS->value) {
                $validated['status'] = SaleStatus::PENDING->value;
            }

            $saleData = SaleData::fromArray($validated);

            $sale = $saleService->createSale($saleData);

            // Generate Pakasir checkout URL if it's a gateway payment (QRIS)
            $checkoutUrl = null;
            if ($sale->payment_method === PaymentMethod::QRIS) {
                $pakasirService = app(PakasirService::class);
                $checkoutUrl = $pakasirService->buildGatewayCheckoutUrl(
                    $sale->invoice_number,
                    (int) $sale->total,
                    route('sales.show', $sale->id),
                    $sale->payment_method->value
                );
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale created successfully',
                    'data' => $sale,
                    'checkout_url' => $checkoutUrl,
                    'print_url' => route('sales.print', $sale->id),
                    'redirect' => $checkoutUrl ?: route('sales.create')
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

    public function destroy(Request $request, Sale $sale, SaleService $saleService)
    {
        try {
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

    public function checkStatus(Sale $sale)
    {
        return response()->json([
            'status' => $sale->status->value,
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
