<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryLedgerService
{
    private AuditService $auditService;

    public function __construct(?AuditService $auditService = null)
    {
        $this->auditService = $auditService ?? app(AuditService::class);
    }

    /**
     * Resolve or lazily provision the default warehouse.
     */
    public function defaultWarehouse(): Warehouse
    {
        return Warehouse::query()->where('is_default', true)->first()
            ?? Warehouse::query()->firstOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'Main Warehouse',
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
    }

    /**
     * Fetch a locked stock balance row, lazily initializing from Product::quantity
     * when creating the default warehouse balance for a newly created product.
     */
    public function getOrCreateBalance(int $productId, ?int $warehouseId = null): StockBalance
    {
        return DB::transaction(function () use ($productId, $warehouseId): StockBalance {
            $warehouse = $this->resolveWarehouse($warehouseId);

            return $this->lockOrCreateBalance($productId, $warehouse);
        });
    }

    /**
     * Return currently available (unreserved) stock for a product in a warehouse.
     */
    public function availableStock(int $productId, ?int $warehouseId = null): int
    {
        $balance = $this->getOrCreateBalance($productId, $warehouseId);

        return max(0, (int) $balance->on_hand - (int) $balance->reserved);
    }

    /**
     * Record incoming stock and optionally decrement expected incoming stock.
     */
    public function stockIn(
        int $productId,
        int $qty,
        string $referenceType,
        ?int $referenceId,
        string $reason,
        ?int $actorId = null,
        ?int $warehouseId = null,
        bool $reduceIncoming = false
    ): StockMovement {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Stock in quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $productId,
            $qty,
            $referenceType,
            $referenceId,
            $reason,
            $actorId,
            $warehouseId,
            $reduceIncoming
        ): StockMovement {
            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            $balance = $this->lockOrCreateBalance($productId, $warehouse, $product);

            $before = (int) $balance->on_hand;
            $balance->on_hand = $before + $qty;

            if ($reduceIncoming) {
                $balance->incoming = max(0, (int) $balance->incoming - $qty);
            }

            $balance->save();
            $this->syncDefaultProductQuantity($product, $balance, $warehouse);

            return $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $productId,
                type: StockMovement::TYPE_IN,
                qty: $qty,
                balanceBefore: $before,
                balanceAfter: (int) $balance->on_hand,
                referenceType: $referenceType,
                referenceId: $referenceId,
                reason: $reason,
                actorId: $actorId
            );
        });
    }

    /**
     * Record outgoing stock from available or reserved inventory.
     */
    public function stockOut(
        int $productId,
        int $qty,
        string $referenceType,
        ?int $referenceId,
        string $reason,
        ?int $actorId = null,
        ?int $warehouseId = null,
        bool $fromReserved = false
    ): StockMovement {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Stock out quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $productId,
            $qty,
            $referenceType,
            $referenceId,
            $reason,
            $actorId,
            $warehouseId,
            $fromReserved
        ): StockMovement {
            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            $balance = $this->lockOrCreateBalance($productId, $warehouse, $product);

            $before = (int) $balance->on_hand;

            if ($fromReserved) {
                if ($before < $qty || (int) $balance->reserved < $qty) {
                    throw new \RuntimeException("Insufficient reserved stock for product #{$productId}.");
                }

                $balance->on_hand = $before - $qty;
                $balance->reserved = max(0, (int) $balance->reserved - $qty);
            } else {
                $available = max(0, $before - (int) $balance->reserved);
                if ($available < $qty) {
                    throw new \RuntimeException("Insufficient available stock for product #{$productId}.");
                }

                $balance->on_hand = $before - $qty;
            }

            $balance->save();
            $this->syncDefaultProductQuantity($product, $balance, $warehouse);

            return $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $productId,
                type: StockMovement::TYPE_OUT,
                qty: $qty,
                balanceBefore: $before,
                balanceAfter: (int) $balance->on_hand,
                referenceType: $referenceType,
                referenceId: $referenceId,
                reason: $reason,
                actorId: $actorId
            );
        });
    }

    /**
     * Reserve available stock for an order or pending transaction.
     *
     * Note: RESERVE and RELEASE movements record available stock (on_hand - reserved)
     * in balance_before and balance_after so reservation availability shifts are auditable.
     */
    public function reserveStock(
        int $productId,
        int $qty,
        string $referenceType,
        ?int $referenceId,
        string $reason = 'Order reservation',
        ?int $orderId = null,
        ?int $actorId = null,
        ?int $warehouseId = null,
        ?Carbon $expiresAt = null
    ): StockReservation {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Reservation quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $productId,
            $qty,
            $referenceType,
            $referenceId,
            $reason,
            $orderId,
            $actorId,
            $warehouseId,
            $expiresAt
        ): StockReservation {
            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            $balance = $this->lockOrCreateBalance($productId, $warehouse, $product);

            $beforeAvailable = max(0, (int) $balance->on_hand - (int) $balance->reserved);
            if ($beforeAvailable < $qty) {
                throw new \RuntimeException("Insufficient available stock to reserve for product #{$productId}.");
            }

            $balance->reserved = (int) $balance->reserved + $qty;
            $balance->save();

            $afterAvailable = max(0, (int) $balance->on_hand - (int) $balance->reserved);

            $reservation = StockReservation::query()->create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $productId,
                'order_id' => $orderId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $qty,
                'status' => StockReservation::STATUS_ACTIVE,
                'expires_at' => $expiresAt,
                'created_by' => $actorId ?? Auth::id(),
            ]);

            $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $productId,
                type: StockMovement::TYPE_RESERVE,
                qty: $qty,
                balanceBefore: $beforeAvailable,
                balanceAfter: $afterAvailable,
                referenceType: $referenceType,
                referenceId: $referenceId ?? $reservation->id,
                reason: $reason,
                actorId: $actorId
            );

            return $reservation;
        });
    }

    /**
     * Release an active reservation idempotently.
     *
     * Note: balance_before and balance_after reflect available stock (on_hand - reserved).
     */
    public function releaseReservation(
        StockReservation $reservation,
        string $reason = 'Reservation released',
        ?int $actorId = null
    ): StockMovement {
        return DB::transaction(function () use ($reservation, $reason, $actorId): StockMovement {
            $locked = StockReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $referenceType = $locked->reference_type ?? StockReservation::class;
            $referenceId = $locked->reference_id ?? $locked->id;

            if ($locked->status !== StockReservation::STATUS_ACTIVE) {
                $existing = StockMovement::query()
                    ->where('warehouse_id', $locked->warehouse_id)
                    ->where('product_id', $locked->product_id)
                    ->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId)
                    ->orderByDesc('id')
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $balance = $this->lockOrCreateBalance($locked->product_id, $this->resolveWarehouse($locked->warehouse_id));
                $available = max(0, (int) $balance->on_hand - (int) $balance->reserved);

                return new StockMovement([
                    'warehouse_id' => $locked->warehouse_id,
                    'product_id' => $locked->product_id,
                    'type' => StockMovement::TYPE_RELEASE,
                    'qty' => 0,
                    'balance_before' => $available,
                    'balance_after' => $available,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'reason' => $reason,
                    'created_by' => $actorId ?? Auth::id(),
                    'created_at' => now(),
                ]);
            }

            $warehouse = $this->resolveWarehouse($locked->warehouse_id);
            $balance = $this->lockOrCreateBalance($locked->product_id, $warehouse);

            $beforeAvailable = max(0, (int) $balance->on_hand - (int) $balance->reserved);
            $balance->reserved = max(0, (int) $balance->reserved - (int) $locked->quantity);
            $balance->save();
            $afterAvailable = max(0, (int) $balance->on_hand - (int) $balance->reserved);

            $locked->status = StockReservation::STATUS_RELEASED;
            $locked->save();

            return $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $locked->product_id,
                type: StockMovement::TYPE_RELEASE,
                qty: (int) $locked->quantity,
                balanceBefore: $beforeAvailable,
                balanceAfter: $afterAvailable,
                referenceType: $referenceType,
                referenceId: $referenceId,
                reason: $reason,
                actorId: $actorId
            );
        });
    }

    /**
     * Consume an active reservation and deduct physical on_hand stock.
     */
    public function consumeReservation(
        StockReservation $reservation,
        string $reason = 'Order fulfilled',
        ?int $actorId = null
    ): StockMovement {
        return DB::transaction(function () use ($reservation, $reason, $actorId): StockMovement {
            $locked = StockReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== StockReservation::STATUS_ACTIVE) {
                throw new \RuntimeException('Only active stock reservations can be consumed.');
            }

            $warehouse = $this->resolveWarehouse($locked->warehouse_id);
            $product = Product::query()->whereKey($locked->product_id)->lockForUpdate()->firstOrFail();
            $balance = $this->lockOrCreateBalance($locked->product_id, $warehouse, $product);

            $qty = (int) $locked->quantity;
            $beforeOnHand = (int) $balance->on_hand;

            if ($beforeOnHand < $qty || (int) $balance->reserved < $qty) {
                throw new \RuntimeException("Insufficient reserved stock to consume reservation #{$locked->id}.");
            }

            $balance->reserved = max(0, (int) $balance->reserved - $qty);
            $balance->on_hand = $beforeOnHand - $qty;
            $balance->save();
            $this->syncDefaultProductQuantity($product, $balance, $warehouse);

            $locked->status = StockReservation::STATUS_CONSUMED;
            $locked->save();

            return $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $locked->product_id,
                type: StockMovement::TYPE_OUT,
                qty: $qty,
                balanceBefore: $beforeOnHand,
                balanceAfter: (int) $balance->on_hand,
                referenceType: $locked->reference_type ?? StockReservation::class,
                referenceId: $locked->reference_id ?? $locked->id,
                reason: $reason,
                actorId: $actorId
            );
        });
    }

    /**
     * Perform a manual or opname stock adjustment with mandatory audit reason.
     */
    public function adjustStock(
        int $productId,
        int $deltaQty,
        string $reason,
        ?int $actorId = null,
        ?int $warehouseId = null,
        string $referenceType = 'adjustment',
        ?int $referenceId = null
    ): StockMovement {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new \InvalidArgumentException('A reason is required for stock adjustments.');
        }

        if ($deltaQty === 0) {
            throw new \InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        return DB::transaction(function () use (
            $productId,
            $deltaQty,
            $cleanReason,
            $actorId,
            $warehouseId,
            $referenceType,
            $referenceId
        ): StockMovement {
            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            $balance = $this->lockOrCreateBalance($productId, $warehouse, $product);

            $before = (int) $balance->on_hand;
            $after = $before + $deltaQty;

            if ($after < 0 || $after < (int) $balance->reserved) {
                throw new \RuntimeException('Adjustment would reduce on-hand stock below zero or reserved quantity.');
            }

            $balance->on_hand = $after;
            $balance->save();
            $this->syncDefaultProductQuantity($product, $balance, $warehouse);

            $movement = $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $productId,
                type: StockMovement::TYPE_ADJUSTMENT,
                qty: $deltaQty,
                balanceBefore: $before,
                balanceAfter: $after,
                referenceType: $referenceType,
                referenceId: $referenceId,
                reason: $cleanReason,
                actorId: $actorId
            );

            $this->auditService->log(
                action: 'stock.adjusted',
                entity: $balance,
                before: [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $productId,
                    'on_hand' => $before,
                ],
                after: [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $productId,
                    'on_hand' => $after,
                    'delta_qty' => $deltaQty,
                ],
                reason: $cleanReason,
                actorId: $actorId
            );

            return $movement;
        });
    }

    /**
     * Adjust expected incoming stock (e.g. open purchase orders) clamped to >= 0.
     */
    public function adjustIncoming(int $productId, int $deltaQty, ?int $warehouseId = null): StockBalance
    {
        return DB::transaction(function () use ($productId, $deltaQty, $warehouseId): StockBalance {
            $warehouse = $this->resolveWarehouse($warehouseId);
            $balance = $this->lockOrCreateBalance($productId, $warehouse);

            $balance->incoming = max(0, (int) $balance->incoming + $deltaQty);
            $balance->save();

            return $balance;
        });
    }

    /**
     * Record stock returned out to a supplier.
     */
    public function returnOut(
        int $productId,
        int $qty,
        string $referenceType,
        ?int $referenceId,
        string $reason,
        ?int $actorId = null,
        ?int $warehouseId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Return out quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $productId,
            $qty,
            $referenceType,
            $referenceId,
            $reason,
            $actorId,
            $warehouseId
        ): StockMovement {
            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            $balance = $this->lockOrCreateBalance($productId, $warehouse, $product);

            $before = (int) $balance->on_hand;
            $available = max(0, $before - (int) $balance->reserved);

            if ($available < $qty) {
                throw new \RuntimeException("Insufficient available stock for return out on product #{$productId}.");
            }

            $balance->on_hand = $before - $qty;
            $balance->save();
            $this->syncDefaultProductQuantity($product, $balance, $warehouse);

            return $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $productId,
                type: StockMovement::TYPE_RETURN_OUT,
                qty: $qty,
                balanceBefore: $before,
                balanceAfter: (int) $balance->on_hand,
                referenceType: $referenceType,
                referenceId: $referenceId,
                reason: $reason,
                actorId: $actorId
            );
        });
    }

    /**
     * Record stock returned in from a customer.
     */
    public function returnIn(
        int $productId,
        int $qty,
        string $referenceType,
        ?int $referenceId,
        string $reason,
        ?int $actorId = null,
        ?int $warehouseId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Return in quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $productId,
            $qty,
            $referenceType,
            $referenceId,
            $reason,
            $actorId,
            $warehouseId
        ): StockMovement {
            $warehouse = $this->resolveWarehouse($warehouseId);
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            $balance = $this->lockOrCreateBalance($productId, $warehouse, $product);

            $before = (int) $balance->on_hand;
            $balance->on_hand = $before + $qty;
            $balance->save();
            $this->syncDefaultProductQuantity($product, $balance, $warehouse);

            return $this->recordMovement(
                warehouseId: $warehouse->id,
                productId: $productId,
                type: StockMovement::TYPE_RETURN_IN,
                qty: $qty,
                balanceBefore: $before,
                balanceAfter: (int) $balance->on_hand,
                referenceType: $referenceType,
                referenceId: $referenceId,
                reason: $reason,
                actorId: $actorId
            );
        });
    }

    private function resolveWarehouse(?int $warehouseId): Warehouse
    {
        if ($warehouseId === null) {
            return $this->defaultWarehouse();
        }

        return Warehouse::query()->findOrFail($warehouseId);
    }

    private function lockOrCreateBalance(int $productId, Warehouse $warehouse, ?Product $lockedProduct = null): StockBalance
    {
        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        $product = $lockedProduct ?? Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
        $initialOnHand = $warehouse->is_default ? max(0, (int) $product->quantity) : 0;
        $now = now();

        StockBalance::query()->insertOrIgnore([
            'warehouse_id' => $warehouse->id,
            'product_id' => $productId,
            'on_hand' => $initialOnHand,
            'reserved' => 0,
            'incoming' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function syncDefaultProductQuantity(Product $product, StockBalance $balance, Warehouse $warehouse): void
    {
        $defaultWarehouse = $this->defaultWarehouse();

        if ($warehouse->id === $defaultWarehouse->id) {
            Product::query()->whereKey($product->id)->update([
                'quantity' => (int) $balance->on_hand,
            ]);
        }
    }

    private function recordMovement(
        int $warehouseId,
        int $productId,
        string $type,
        int $qty,
        int $balanceBefore,
        int $balanceAfter,
        ?string $referenceType,
        ?int $referenceId,
        ?string $reason,
        ?int $actorId
    ): StockMovement {
        return StockMovement::query()->create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'type' => $type,
            'qty' => $qty,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'created_by' => $actorId ?? Auth::id(),
            'created_at' => now(),
        ]);
    }
}
