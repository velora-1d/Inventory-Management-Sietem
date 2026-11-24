<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\InventoryLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request, InventoryLedgerService $ledger): View
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $defaultWarehouse = $ledger->defaultWarehouse();
        $selectedWarehouseId = $request->filled('warehouse_id')
            ? (int) $request->input('warehouse_id')
            : $defaultWarehouse->id;

        if ($selectedWarehouseId === $defaultWarehouse->id) {
            $this->syncMissingDefaultBalances($defaultWarehouse->id);
        }

        $search = trim((string) $request->input('search', ''));
        $stockStatus = (string) $request->input('stock_status', '');

        $query = StockBalance::query()
            ->with(['warehouse', 'product.category', 'product.unit'])
            ->where('warehouse_id', $selectedWarehouseId);

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->whereHas('product', function ($productQuery) use ($pattern): void {
                $productQuery->whereRaw('LOWER(name) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$pattern]);
            });
        }

        if ($stockStatus === 'out') {
            $query->whereRaw('(on_hand - reserved) <= 0');
        } elseif ($stockStatus === 'low') {
            $query->whereHas('product', function ($productQuery): void {
                $productQuery->whereRaw('(stock_balances.on_hand - stock_balances.reserved) <= products.min_stock');
            });
        }

        $balances = $query
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $summaryRow = StockBalance::query()
            ->where('warehouse_id', $selectedWarehouseId)
            ->selectRaw('COUNT(*) as total_skus, COALESCE(SUM(on_hand), 0) as total_on_hand, COALESCE(SUM(reserved), 0) as total_reserved, COALESCE(SUM(incoming), 0) as total_incoming')
            ->first();

        $summary = [
            'total_skus' => (int) ($summaryRow->total_skus ?? 0),
            'total_on_hand' => (int) ($summaryRow->total_on_hand ?? 0),
            'total_reserved' => (int) ($summaryRow->total_reserved ?? 0),
            'total_available' => max(0, (int) ($summaryRow->total_on_hand ?? 0) - (int) ($summaryRow->total_reserved ?? 0)),
            'total_incoming' => (int) ($summaryRow->total_incoming ?? 0),
        ];

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'quantity']);

        return view('inventory.index', compact(
            'balances',
            'warehouses',
            'selectedWarehouseId',
            'search',
            'stockStatus',
            'summary',
            'products'
        ));
    }

    public function movements(Request $request): View
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $search = trim((string) $request->input('search', ''));
        $type = trim((string) $request->input('type', ''));
        $warehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $query = StockMovement::query()
            ->with(['warehouse', 'product', 'creator']);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(COALESCE(reason, \'\')) LIKE ?', [$pattern])
                    ->orWhereHas('product', function ($productQuery) use ($pattern): void {
                        $productQuery->whereRaw('LOWER(name) LIKE ?', [$pattern])
                            ->orWhereRaw('LOWER(sku) LIKE ?', [$pattern]);
                    });
            });
        }

        $movements = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $movementTypes = [
            StockMovement::TYPE_IN,
            StockMovement::TYPE_OUT,
            StockMovement::TYPE_RESERVE,
            StockMovement::TYPE_RELEASE,
            StockMovement::TYPE_ADJUSTMENT,
            StockMovement::TYPE_RETURN_OUT,
            StockMovement::TYPE_RETURN_IN,
        ];

        return view('inventory.movements', compact(
            'movements',
            'warehouses',
            'search',
            'type',
            'warehouseId',
            'movementTypes'
        ));
    }

    public function adjust(Request $request, InventoryLedgerService $ledger): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'delta_qty' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        try {
            $ledger->adjustStock(
                productId: (int) $validated['product_id'],
                deltaQty: (int) $validated['delta_qty'],
                reason: $validated['reason'],
                actorId: Auth::id(),
                warehouseId: isset($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null
            );
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Stock adjustment recorded successfully.');
    }

    public function opnames(Request $request, InventoryLedgerService $ledger): View
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $defaultWarehouse = $ledger->defaultWarehouse();

        $opnames = StockOpname::query()
            ->with(['warehouse', 'creator', 'approver', 'items.product'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'quantity']);

        $balancesByProduct = StockBalance::query()
            ->where('warehouse_id', $defaultWarehouse->id)
            ->pluck('on_hand', 'product_id');

        return view('inventory.opnames', compact(
            'opnames',
            'warehouses',
            'defaultWarehouse',
            'products',
            'balancesByProduct'
        ));
    }

    public function storeOpname(Request $request, InventoryLedgerService $ledger): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.counted_qty' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $opname = DB::transaction(function () use ($validated, $ledger): StockOpname {
            $warehouseId = isset($validated['warehouse_id'])
                ? (int) $validated['warehouse_id']
                : $ledger->defaultWarehouse()->id;

            $opname = StockOpname::query()->create([
                'opname_number' => 'OPN-' . now()->format('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2))),
                'warehouse_id' => $warehouseId,
                'status' => StockOpname::STATUS_DRAFT,
                'reason' => $validated['reason'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $productId = (int) $item['product_id'];
                $balance = $ledger->getOrCreateBalance($productId, $warehouseId);
                $systemQty = (int) $balance->on_hand;
                $countedQty = (int) $item['counted_qty'];

                StockOpnameItem::query()->create([
                    'stock_opname_id' => $opname->id,
                    'product_id' => $productId,
                    'system_qty' => $systemQty,
                    'counted_qty' => $countedQty,
                    'difference_qty' => $countedQty - $systemQty,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $opname;
        });

        return redirect()->back()->with('success', "Stock opname {$opname->opname_number} created.");
    }

    public function approveOpname(
        StockOpname $opname,
        InventoryLedgerService $ledger,
        AuditService $auditService
    ): RedirectResponse {
        try {
            $approvedOpname = DB::transaction(function () use ($opname, $ledger, $auditService): StockOpname {
                $lockedOpname = StockOpname::query()
                    ->with('items')
                    ->whereKey($opname->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($lockedOpname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_SUBMITTED], true)) {
                    throw new \RuntimeException('Only draft or submitted stock opnames can be approved.');
                }

                $previousStatus = $lockedOpname->status;

                foreach ($lockedOpname->items as $item) {
                    if ((int) $item->difference_qty === 0) {
                        continue;
                    }

                    $reason = "Stock opname {$lockedOpname->opname_number}";
                    if (! empty($item->notes)) {
                        $reason .= " ({$item->notes})";
                    }

                    $ledger->adjustStock(
                        productId: (int) $item->product_id,
                        deltaQty: (int) $item->difference_qty,
                        reason: $reason,
                        actorId: Auth::id(),
                        warehouseId: (int) $lockedOpname->warehouse_id,
                        referenceType: StockOpname::class,
                        referenceId: (int) $lockedOpname->id
                    );
                }

                $lockedOpname->status = StockOpname::STATUS_APPROVED;
                $lockedOpname->approved_by = Auth::id();
                $lockedOpname->approved_at = now();
                $lockedOpname->save();

                $auditService->log(
                    action: 'stock_opname.approved',
                    entity: $lockedOpname,
                    before: ['status' => $previousStatus],
                    after: [
                        'status' => StockOpname::STATUS_APPROVED,
                        'items_count' => $lockedOpname->items->count(),
                    ],
                    reason: $lockedOpname->reason ?: "Approved stock opname {$lockedOpname->opname_number}",
                    actorId: Auth::id()
                );

                return $lockedOpname;
            });
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', "Stock opname {$approvedOpname->opname_number} approved and stock adjusted.");
    }

    private function syncMissingDefaultBalances(int $defaultWarehouseId): void
    {
        $existingProductIds = StockBalance::query()
            ->where('warehouse_id', $defaultWarehouseId)
            ->pluck('product_id');

        $missingProducts = Product::query()
            ->when($existingProductIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $existingProductIds))
            ->get(['id', 'quantity']);

        if ($missingProducts->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($missingProducts as $product) {
            $rows[] = [
                'warehouse_id' => $defaultWarehouseId,
                'product_id' => $product->id,
                'on_hand' => max(0, (int) $product->quantity),
                'reserved' => 0,
                'incoming' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        StockBalance::query()->insertOrIgnore($rows);
    }
}
