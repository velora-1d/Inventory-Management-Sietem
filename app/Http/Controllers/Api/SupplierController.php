<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class SupplierController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        $cacheKey = 'suppliers_search_' . md5($query);

        $suppliers = Cache::remember($cacheKey, 300, function () use ($query) {
            return Supplier::query()
                ->when($query, function ($q) use ($query) {
                    $q->where(function ($sub) use ($query) {
                        $sub->whereLike('name', "%{$query}%", caseSensitive: false)
                            ->orWhereLike('phone', "%{$query}%", caseSensitive: false);
                    });
                })
                ->limit(20)
                ->get()
                ->map(function ($supplier) {
                    return [
                        'value' => $supplier->id,
                        'text' => $supplier->name . ($supplier->phone ? ' | ' . $supplier->phone : ''),
                    ];
                });
        });

        return response()->json($suppliers);
    }
}
