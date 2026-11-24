<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class BarcodeLookupController extends Controller
{
    public function lookup(string $code): JsonResponse
    {
        $trimmed = trim($code);

        if ($trimmed === '') {
            return response()->json([
                'message' => 'Produk dengan barcode/SKU tersebut tidak ditemukan.',
            ], 404);
        }

        $product = Product::query()
            ->with('unit')
            ->where('is_active', true)
            ->where(function ($query) use ($trimmed) {
                $query->where('barcode', $trimmed)
                    ->orWhere('sku', $trimmed);
            })
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Produk dengan barcode/SKU tersebut tidak ditemukan.',
            ], 404);
        }

        $available = $this->resolveAvailableStock($product);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'selling_price' => $product->selling_price,
            'purchase_price' => $product->purchase_price,
            'available' => $available,
            'unit' => $product->unit ? [
                'id' => $product->unit->id,
                'name' => $product->unit->name,
                'symbol' => $product->unit->symbol,
            ] : null,
        ]);
    }

    private function resolveAvailableStock(Product $product): int
    {
        if (class_exists(\App\Models\StockBalance::class) && Schema::hasTable('stock_balances')) {
            $balances = \App\Models\StockBalance::where('product_id', $product->id)->get();

            if ($balances->isNotEmpty()) {
                if (Schema::hasColumn('stock_balances', 'on_hand')) {
                    return (int) $balances->sum(fn ($balance) => $balance->available);
                }

                if (Schema::hasColumn('stock_balances', 'available')) {
                    return max(0, (int) $balances->sum('available'));
                }

                if (Schema::hasColumn('stock_balances', 'quantity')) {
                    return max(0, (int) $balances->sum('quantity'));
                }
            }
        }

        return max(0, (int) $product->quantity);
    }
}
