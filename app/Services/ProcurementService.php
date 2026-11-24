<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestApproval;
use App\Models\PurchaseRequestItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementService
{
    private InventoryLedgerService $inventoryLedger;
    private AuditService $auditService;

    public function __construct(
        ?InventoryLedgerService $inventoryLedger = null,
        ?AuditService $auditService = null
    ) {
        $this->inventoryLedger = $inventoryLedger ?? app(InventoryLedgerService::class);
        $this->auditService = $auditService ?? app(AuditService::class);
    }

    /**
     * Create a new Purchase Request with line items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createPurchaseRequest(User $requester, array $items, ?string $notes = null): PurchaseRequest
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('At least one line item is required for a Purchase Request.');
        }

        return DB::transaction(function () use ($requester, $items, $notes): PurchaseRequest {
            $pr = PurchaseRequest::query()->create([
                'pr_number' => $this->generateUniqueNumber('PR', PurchaseRequest::class, 'pr_number'),
                'status' => PurchaseRequest::STATUS_PENDING_APPROVAL,
                'notes' => $notes !== null ? trim($notes) : null,
                'requested_by' => $requester->id,
            ]);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    throw new \InvalidArgumentException('Purchase Request item quantity must be greater than zero.');
                }

                PurchaseRequestItem::query()->create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => $qty,
                    'estimated_unit_price' => round((float) ($item['estimated_unit_price'] ?? 0), 2),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->auditService->log(
                action: 'purchase_request.created',
                entity: $pr,
                before: null,
                after: $pr->load('items')->toArray(),
                reason: $notes,
                actorId: $requester->id
            );

            return $pr->fresh(['items.product', 'requester', 'approvals.actor']);
        });
    }

    /**
     * Approve a pending Purchase Request.
     */
    public function approvePurchaseRequest(PurchaseRequest $pr, User $approver, ?string $notes = null): PurchaseRequest
    {
        return DB::transaction(function () use ($pr, $approver, $notes): PurchaseRequest {
            $locked = PurchaseRequest::query()->whereKey($pr->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PurchaseRequest::STATUS_PENDING_APPROVAL) {
                throw new \DomainException('Only pending purchase requests can be approved.');
            }

            $before = $locked->toArray();

            $locked->update([
                'status' => PurchaseRequest::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            PurchaseRequestApproval::query()->create([
                'purchase_request_id' => $locked->id,
                'actor_id' => $approver->id,
                'decision' => 'approved',
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'purchase_request.approved',
                entity: $locked,
                before: $before,
                after: $locked->toArray(),
                reason: $notes,
                actorId: $approver->id
            );

            return $locked->fresh(['items.product', 'requester', 'approver', 'approvals.actor']);
        });
    }

    /**
     * Reject a pending Purchase Request with mandatory reason.
     */
    public function rejectPurchaseRequest(PurchaseRequest $pr, User $approver, string $reason): PurchaseRequest
    {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new \InvalidArgumentException('Rejection reason is required.');
        }

        return DB::transaction(function () use ($pr, $approver, $cleanReason): PurchaseRequest {
            $locked = PurchaseRequest::query()->whereKey($pr->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PurchaseRequest::STATUS_PENDING_APPROVAL) {
                throw new \DomainException('Only pending purchase requests can be rejected.');
            }

            $before = $locked->toArray();

            $locked->update([
                'status' => PurchaseRequest::STATUS_REJECTED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => $cleanReason,
            ]);

            PurchaseRequestApproval::query()->create([
                'purchase_request_id' => $locked->id,
                'actor_id' => $approver->id,
                'decision' => 'rejected',
                'notes' => $cleanReason,
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'purchase_request.rejected',
                entity: $locked,
                before: $before,
                after: $locked->toArray(),
                reason: $cleanReason,
                actorId: $approver->id
            );

            return $locked->fresh(['items.product', 'requester', 'approver', 'approvals.actor']);
        });
    }

    /**
     * Create a draft Purchase Order.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createPurchaseOrder(
        User $creator,
        int $supplierId,
        array $items,
        ?int $purchaseRequestId = null,
        ?string $expectedDate = null,
        ?string $notes = null
    ): PurchaseOrder {
        if (empty($items)) {
            throw new \InvalidArgumentException('At least one line item is required for a Purchase Order.');
        }

        return DB::transaction(function () use ($creator, $supplierId, $items, $purchaseRequestId, $expectedDate, $notes): PurchaseOrder {
            [$preparedItems, $subtotal] = $this->preparePurchaseOrderItems($items);

            $po = PurchaseOrder::query()->create([
                'po_number' => $this->generateUniqueNumber('PO', PurchaseOrder::class, 'po_number'),
                'supplier_id' => $supplierId,
                'purchase_request_id' => $purchaseRequestId,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'total' => $subtotal,
                'expected_date' => $expectedDate,
                'notes' => $notes !== null ? trim($notes) : null,
                'created_by' => $creator->id,
                'revision_number' => 0,
            ]);

            foreach ($preparedItems as $item) {
                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $this->auditService->log(
                action: 'purchase_order.created',
                entity: $po,
                before: null,
                after: $po->load('items')->toArray(),
                reason: $notes,
                actorId: $creator->id
            );

            return $po->fresh(['supplier', 'purchaseRequest', 'items.product', 'creator']);
        });
    }

    /**
     * Submit a draft Purchase Order for approval.
     */
    public function submitPurchaseOrder(PurchaseOrder $po, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $actor): PurchaseOrder {
            $locked = PurchaseOrder::query()->whereKey($po->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PurchaseOrder::STATUS_DRAFT) {
                throw new \DomainException('Only draft purchase orders can be submitted for approval.');
            }

            $before = $locked->toArray();

            $locked->update([
                'status' => PurchaseOrder::STATUS_PENDING_APPROVAL,
            ]);

            PurchaseOrderApproval::query()->create([
                'purchase_order_id' => $locked->id,
                'actor_id' => $actor->id,
                'decision' => 'submitted',
                'revision_number' => (int) $locked->revision_number,
                'notes' => 'Submitted for approval',
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'purchase_order.submitted',
                entity: $locked,
                before: $before,
                after: $locked->toArray(),
                actorId: $actor->id
            );

            return $locked->fresh(['supplier', 'items.product', 'creator', 'approvals.actor']);
        });
    }

    /**
     * Approve a pending Purchase Order with strict Maker-Checker enforcement and incoming balance update.
     */
    public function approvePurchaseOrder(PurchaseOrder $po, User $approver, ?string $notes = null): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $approver, $notes): PurchaseOrder {
            $locked = PurchaseOrder::query()->with('items')->whereKey($po->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->created_by === (int) $approver->id) {
                throw new \DomainException('Maker-checker violation: PO creator cannot approve their own Purchase Order.');
            }

            if ($locked->status !== PurchaseOrder::STATUS_PENDING_APPROVAL) {
                throw new \DomainException('Only purchase orders pending approval can be approved.');
            }

            $before = $locked->toArray();

            $locked->update([
                'status' => PurchaseOrder::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            PurchaseOrderApproval::query()->create([
                'purchase_order_id' => $locked->id,
                'actor_id' => $approver->id,
                'decision' => 'approved',
                'revision_number' => (int) $locked->revision_number,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'purchase_order.approved',
                entity: $locked,
                before: $before,
                after: $locked->toArray(),
                reason: $notes,
                actorId: $approver->id
            );

            foreach ($locked->items as $item) {
                $this->inventoryLedger->adjustIncoming((int) $item->product_id, (int) $item->quantity_ordered);
            }

            return $locked->fresh(['supplier', 'items.product', 'creator', 'approver', 'approvals.actor']);
        });
    }

    /**
     * Revise a Purchase Order, reverting prior incoming balance if already approved/sent and re-queuing approval.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function revisePurchaseOrder(PurchaseOrder $po, User $actor, array $items, string $reason): PurchaseOrder
    {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new \InvalidArgumentException('Revision reason is required.');
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('At least one line item is required when revising a Purchase Order.');
        }

        return DB::transaction(function () use ($po, $actor, $items, $cleanReason): PurchaseOrder {
            $locked = PurchaseOrder::query()->with('items')->whereKey($po->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_FULLY_RECEIVED, PurchaseOrder::STATUS_CANCELLED], true)) {
                throw new \DomainException('Cannot revise a Purchase Order that is already received or cancelled.');
            }

            $before = $locked->load('items')->toArray();

            if (in_array($locked->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT], true)) {
                foreach ($locked->items as $oldItem) {
                    $this->inventoryLedger->adjustIncoming((int) $oldItem->product_id, -(int) $oldItem->quantity_ordered);
                }
            }

            [$preparedItems, $subtotal] = $this->preparePurchaseOrderItems($items);

            $locked->items()->delete();

            foreach ($preparedItems as $item) {
                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $locked->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $newRevision = (int) $locked->revision_number + 1;

            $locked->update([
                'subtotal' => $subtotal,
                'total' => round($subtotal + (float) $locked->tax_total, 2),
                'revision_number' => $newRevision,
                'status' => PurchaseOrder::STATUS_PENDING_APPROVAL,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            PurchaseOrderApproval::query()->create([
                'purchase_order_id' => $locked->id,
                'actor_id' => $actor->id,
                'decision' => 'revised',
                'revision_number' => $newRevision,
                'notes' => $cleanReason,
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'purchase_order.revised',
                entity: $locked,
                before: $before,
                after: $locked->fresh('items')->toArray(),
                reason: $cleanReason,
                actorId: $actor->id
            );

            return $locked->fresh(['supplier', 'items.product', 'creator', 'approvals.actor']);
        });
    }

    /**
     * Record a Goods Receipt Note (GRN) against an approved/sent/partially_received Purchase Order.
     *
     * @param array<int, array<string, mixed>> $receivedItems
     */
    public function receiveGoods(
        PurchaseOrder $po,
        User $receiver,
        array $receivedItems,
        ?int $warehouseId = null,
        string $qcStatus = 'passed',
        ?string $notes = null
    ): GoodsReceipt {
        if (empty($receivedItems)) {
            throw new \InvalidArgumentException('At least one received item is required.');
        }

        return DB::transaction(function () use ($po, $receiver, $receivedItems, $warehouseId, $qcStatus, $notes): GoodsReceipt {
            $locked = PurchaseOrder::query()->with('items')->whereKey($po->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
                throw new \DomainException('Goods can only be received for approved, sent, or partially received Purchase Orders.');
            }

            $beforePo = $locked->toArray();
            $targetWarehouseId = $warehouseId ?? $this->inventoryLedger->defaultWarehouse()->id;

            $grn = GoodsReceipt::query()->create([
                'grn_number' => $this->generateUniqueNumber('GRN', GoodsReceipt::class, 'grn_number'),
                'purchase_order_id' => $locked->id,
                'warehouse_id' => $targetWarehouseId,
                'received_by' => $receiver->id,
                'received_at' => now(),
                'qc_status' => $qcStatus,
                'notes' => $notes !== null ? trim($notes) : null,
            ]);

            $totalReceivedThisGrn = 0;

            foreach ($receivedItems as $entry) {
                $qtyReceived = (int) ($entry['quantity_received'] ?? 0);
                if ($qtyReceived < 0) {
                    throw new \InvalidArgumentException('Received quantity cannot be negative.');
                }

                if ($qtyReceived === 0) {
                    continue;
                }

                $poItem = PurchaseOrderItem::query()
                    ->where('purchase_order_id', $locked->id)
                    ->whereKey((int) $entry['purchase_order_item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $poItem->quantity_received + $qtyReceived > (int) $poItem->quantity_ordered) {
                    throw new \DomainException("Over-receipt prevented for PO item #{$poItem->id}: ordered {$poItem->quantity_ordered}, already received {$poItem->quantity_received}, attempted {$qtyReceived}.");
                }

                $acceptedQty = array_key_exists('quantity_accepted', $entry) && $entry['quantity_accepted'] !== null
                    ? (int) $entry['quantity_accepted']
                    : $qtyReceived;
                $rejectedQty = array_key_exists('quantity_rejected', $entry) && $entry['quantity_rejected'] !== null
                    ? (int) $entry['quantity_rejected']
                    : max(0, $qtyReceived - $acceptedQty);

                if ($acceptedQty < 0 || $rejectedQty < 0 || ($acceptedQty + $rejectedQty) > $qtyReceived) {
                    throw new \DomainException("Accepted ({$acceptedQty}) and rejected ({$rejectedQty}) quantities exceed received quantity ({$qtyReceived}).");
                }

                GoodsReceiptItem::query()->create([
                    'goods_receipt_id' => $grn->id,
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'quantity_received' => $qtyReceived,
                    'quantity_accepted' => $acceptedQty,
                    'quantity_rejected' => $rejectedQty,
                    'notes' => $entry['notes'] ?? null,
                ]);

                if ($acceptedQty > 0) {
                    $this->inventoryLedger->stockIn(
                        productId: (int) $poItem->product_id,
                        qty: $acceptedQty,
                        referenceType: 'goods_receipt',
                        referenceId: $grn->id,
                        reason: "GRN #{$grn->grn_number} for PO #{$locked->po_number}",
                        actorId: $receiver->id,
                        warehouseId: $targetWarehouseId,
                        reduceIncoming: true
                    );
                }

                $poItem->quantity_received = (int) $poItem->quantity_received + $qtyReceived;
                $poItem->save();

                $totalReceivedThisGrn += $qtyReceived;
            }

            if ($totalReceivedThisGrn <= 0) {
                throw new \InvalidArgumentException('At least one item must have a received quantity greater than zero.');
            }

            $locked->load('items');
            $allReceived = $locked->items->every(
                fn (PurchaseOrderItem $item): bool => (int) $item->quantity_received >= (int) $item->quantity_ordered
            );

            $locked->status = $allReceived
                ? PurchaseOrder::STATUS_FULLY_RECEIVED
                : PurchaseOrder::STATUS_PARTIALLY_RECEIVED;
            $locked->save();

            $this->auditService->log(
                action: 'goods_receipt.recorded',
                entity: $grn,
                before: $beforePo,
                after: [
                    'grn' => $grn->load('items')->toArray(),
                    'po_status' => $locked->status,
                ],
                reason: $notes,
                actorId: $receiver->id
            );

            return $grn->fresh(['purchaseOrder', 'warehouse', 'receiver', 'items.product', 'items.purchaseOrderItem']);
        });
    }

    /**
     * Record a Supplier Invoice and execute 3-Way Match (PO vs GRN accepted qty vs Invoice amount).
     *
     * @param array<string, mixed> $data
     */
    public function recordSupplierInvoice(PurchaseOrder $po, User $actor, array $data): SupplierInvoice
    {
        return DB::transaction(function () use ($po, $actor, $data): SupplierInvoice {
            $locked = PurchaseOrder::query()->with('items')->whereKey($po->id)->lockForUpdate()->firstOrFail();

            $grnItems = GoodsReceiptItem::query()
                ->with('purchaseOrderItem')
                ->whereHas('goodsReceipt', function ($query) use ($locked): void {
                    $query->where('purchase_order_id', $locked->id);
                })
                ->get();

            $receivedTotalQty = (int) $grnItems->sum('quantity_accepted');
            $expectedAmount = 0.0;

            foreach ($grnItems as $grnItem) {
                $unitPrice = (float) ($grnItem->purchaseOrderItem->unit_price ?? 0);
                $expectedAmount += ((int) $grnItem->quantity_accepted) * $unitPrice;
            }

            $expectedAmount = round($expectedAmount, 2);
            $invoiceAmount = round((float) $data['amount'], 2);
            $isMatched = $receivedTotalQty > 0 && abs($invoiceAmount - $expectedAmount) <= 0.01;

            if ($receivedTotalQty <= 0) {
                $matchNote = 'No accepted goods receipt quantity found for 3-way match.';
            } elseif ($isMatched) {
                $matchNote = '3-way match passed (accepted qty: ' . $receivedTotalQty . ', expected: ' . number_format($expectedAmount, 2, '.', '') . ', invoiced: ' . number_format($invoiceAmount, 2, '.', '') . ').';
            } else {
                $matchNote = '3-way match discrepancy: expected received value ' . number_format($expectedAmount, 2, '.', '') . ' vs invoice amount ' . number_format($invoiceAmount, 2, '.', '') . '.';
            }

            if (! empty($data['match_notes'])) {
                $matchNote = trim((string) $data['match_notes']) . ' | ' . $matchNote;
            }

            $invoice = SupplierInvoice::query()->create([
                'invoice_number' => trim((string) $data['invoice_number']),
                'supplier_id' => (int) ($data['supplier_id'] ?? $locked->supplier_id),
                'purchase_order_id' => $locked->id,
                'goods_receipt_id' => ! empty($data['goods_receipt_id']) ? (int) $data['goods_receipt_id'] : null,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'amount' => $invoiceAmount,
                'match_status' => $isMatched ? SupplierInvoice::MATCH_MATCHED : SupplierInvoice::MATCH_DISCREPANCY,
                'match_notes' => $matchNote,
                'status' => $isMatched ? SupplierInvoice::STATUS_VERIFIED : SupplierInvoice::STATUS_DRAFT,
                'verified_by' => $isMatched ? $actor->id : null,
            ]);

            if ($isMatched && class_exists(\App\Services\AccountsPayableService::class)) {
                app(\App\Services\AccountsPayableService::class)->createFromSupplierInvoice($invoice);
            }

            $this->auditService->log(
                action: 'supplier_invoice.recorded',
                entity: $invoice,
                before: null,
                after: $invoice->toArray(),
                reason: $matchNote,
                actorId: $actor->id
            );

            return $invoice->fresh(['supplier', 'purchaseOrder', 'goodsReceipt', 'verifier']);
        });
    }

    /**
     * Create a Purchase Return and deduct stock via InventoryLedgerService::returnOut.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createPurchaseReturn(
        PurchaseOrder $po,
        User $actor,
        array $items,
        string $reason,
        ?int $goodsReceiptId = null
    ): PurchaseReturn {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new \InvalidArgumentException('A reason is required for a Purchase Return.');
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('At least one return item is required.');
        }

        return DB::transaction(function () use ($po, $actor, $items, $cleanReason, $goodsReceiptId): PurchaseReturn {
            $locked = PurchaseOrder::query()->with('items')->whereKey($po->id)->lockForUpdate()->firstOrFail();

            $prepared = [];
            $totalAmount = 0.0;

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) ($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    throw new \InvalidArgumentException('Return quantity must be greater than zero.');
                }

                $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                    ? round((float) $item['unit_price'], 2)
                    : round((float) ($locked->items->firstWhere('product_id', $productId)?->unit_price ?? 0), 2);

                $lineSubtotal = round($qty * $unitPrice, 2);
                $totalAmount += $lineSubtotal;

                $prepared[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $return = PurchaseReturn::query()->create([
                'return_number' => $this->generateUniqueNumber('RET', PurchaseReturn::class, 'return_number'),
                'goods_receipt_id' => $goodsReceiptId,
                'purchase_order_id' => $locked->id,
                'supplier_id' => $locked->supplier_id,
                'reason' => $cleanReason,
                'status' => 'completed',
                'total_amount' => round($totalAmount, 2),
                'created_by' => $actor->id,
            ]);

            foreach ($prepared as $line) {
                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                $this->inventoryLedger->returnOut(
                    productId: $line['product_id'],
                    qty: $line['quantity'],
                    referenceType: 'purchase_return',
                    referenceId: $return->id,
                    reason: $cleanReason,
                    actorId: $actor->id
                );
            }

            $this->auditService->log(
                action: 'purchase_return.created',
                entity: $return,
                before: null,
                after: $return->load('items')->toArray(),
                reason: $cleanReason,
                actorId: $actor->id
            );

            return $return->fresh(['purchaseOrder', 'goodsReceipt', 'supplier', 'creator', 'items.product']);
        });
    }

    /**
     * Normalize and total Purchase Order items.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array{0: array<int, array{product_id: int, quantity_ordered: int, unit_price: float, subtotal: float}>, 1: float}
     */
    private function preparePurchaseOrderItems(array $items): array
    {
        $prepared = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $qty = (int) ($item['quantity_ordered'] ?? $item['quantity'] ?? 0);
            if ($qty <= 0) {
                throw new \InvalidArgumentException('Purchase Order item quantity must be greater than zero.');
            }

            $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);
            if ($unitPrice < 0) {
                throw new \InvalidArgumentException('Unit price cannot be negative.');
            }

            $lineSubtotal = round($qty * $unitPrice, 2);
            $subtotal += $lineSubtotal;

            $prepared[] = [
                'product_id' => (int) $item['product_id'],
                'quantity_ordered' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        return [$prepared, round($subtotal, 2)];
    }

    /**
     * Generate a collision-free document number.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    private function generateUniqueNumber(string $prefix, string $modelClass, string $column): string
    {
        do {
            $candidate = sprintf('%s-%s-%s', $prefix, now()->format('Ymd'), Str::upper(Str::random(6)));
        } while ($modelClass::query()->where($column, $candidate)->exists());

        return $candidate;
    }
}
