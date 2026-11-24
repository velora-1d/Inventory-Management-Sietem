<?php

namespace App\Services;

use App\Enums\FinanceCategoryType;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Create a new refund request.
     */
    public function requestRefund(
        User $actor,
        float $amount,
        string $method,
        string $reason,
        ?int $orderId = null,
        ?int $saleId = null
    ): Refund {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \DomainException('Refund amount must be greater than zero.');
        }

        if ($orderId !== null) {
            Order::findOrFail($orderId);
        }

        if ($saleId !== null) {
            Sale::findOrFail($saleId);
        }

        return DB::transaction(function () use ($actor, $amount, $method, $reason, $orderId, $saleId): Refund {
            $refund = Refund::create([
                'refund_number' => $this->generateRefundNumber(),
                'order_id' => $orderId,
                'sale_id' => $saleId,
                'amount' => $amount,
                'method' => $method,
                'status' => Refund::STATUS_REQUESTED,
                'reason' => $reason,
                'requested_by' => $actor->id,
            ]);

            $this->auditService->log(
                'refund.requested',
                $refund,
                null,
                [
                    'refund_number' => $refund->refund_number,
                    'order_id' => $refund->order_id,
                    'sale_id' => $refund->sale_id,
                    'amount' => $refund->amount,
                    'method' => $refund->method,
                    'status' => $refund->status,
                ],
                $reason,
                $actor->id
            );

            return $refund;
        });
    }

    /**
     * Approve a requested refund.
     */
    public function approveRefund(Refund $refund, User $approver): Refund
    {
        return DB::transaction(function () use ($refund, $approver): Refund {
            $lockedRefund = Refund::query()
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRefund->status !== Refund::STATUS_REQUESTED) {
                throw new \DomainException('Only requested refunds can be approved.');
            }

            $before = ['status' => $lockedRefund->status];

            $lockedRefund->update([
                'status' => Refund::STATUS_APPROVED,
            ]);

            $this->auditService->log(
                'refund.approved',
                $lockedRefund,
                $before,
                ['status' => $lockedRefund->status],
                null,
                $approver->id
            );

            return $lockedRefund->fresh();
        });
    }

    /**
     * Reject a requested or approved refund.
     */
    public function rejectRefund(Refund $refund, User $approver, string $reason): Refund
    {
        return DB::transaction(function () use ($refund, $approver, $reason): Refund {
            $lockedRefund = Refund::query()
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedRefund->status, [Refund::STATUS_REQUESTED, Refund::STATUS_APPROVED], true)) {
                throw new \DomainException('Only requested or approved refunds can be rejected.');
            }

            $before = ['status' => $lockedRefund->status];

            $lockedRefund->update([
                'status' => Refund::STATUS_REJECTED,
                'processed_by' => $approver->id,
                'processed_at' => now(),
            ]);

            $this->auditService->log(
                'refund.rejected',
                $lockedRefund,
                $before,
                [
                    'status' => $lockedRefund->status,
                    'processed_by' => $lockedRefund->processed_by,
                ],
                $reason,
                $approver->id
            );

            return $lockedRefund->fresh();
        });
    }

    /**
     * Process a requested or approved refund, record expense finance transaction,
     * and update linked order payment_status to 'refunded' without altering status or shipment_status.
     */
    public function processRefund(Refund $refund, User $processor, ?string $reference = null): Refund
    {
        return DB::transaction(function () use ($refund, $processor, $reference): Refund {
            $lockedRefund = Refund::query()
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedRefund->status, [Refund::STATUS_REQUESTED, Refund::STATUS_APPROVED], true)) {
                throw new \DomainException('Refund must be in requested or approved status to process.');
            }

            $before = [
                'status' => $lockedRefund->status,
                'reference' => $lockedRefund->reference,
            ];

            $categoryName = 'Refund Pelanggan';
            $category = FinanceCategory::firstOrCreate(
                ['name' => $categoryName],
                [
                    'slug' => Str::slug($categoryName),
                    'type' => FinanceCategoryType::Expense,
                    'description' => 'Pengeluaran refund pelanggan',
                ]
            );

            $transaction = FinanceTransaction::create([
                'code' => $this->generateTransactionCode('RFD'),
                'transaction_date' => now()->toDateString(),
                'finance_category_id' => $category->id,
                'amount' => (int) round((float) $lockedRefund->amount),
                'description' => 'Refund ' . $lockedRefund->refund_number . ' - ' . $lockedRefund->reason,
                'external_reference' => $reference ?: $lockedRefund->refund_number,
                'reference_type' => Refund::class,
                'reference_id' => $lockedRefund->id,
                'created_by' => $processor->id,
            ]);

            $lockedRefund->update([
                'status' => Refund::STATUS_PROCESSED,
                'processed_by' => $processor->id,
                'processed_at' => now(),
                'reference' => $reference ?: $lockedRefund->reference,
            ]);

            if ($lockedRefund->order_id) {
                Order::whereKey($lockedRefund->order_id)->update([
                    'payment_status' => 'refunded',
                ]);
            }

            $this->auditService->log(
                'refund.processed',
                $lockedRefund,
                $before,
                [
                    'status' => $lockedRefund->status,
                    'processed_by' => $lockedRefund->processed_by,
                    'reference' => $lockedRefund->reference,
                    'finance_transaction_id' => $transaction->id,
                ],
                $lockedRefund->reason,
                $processor->id
            );

            return $lockedRefund->fresh();
        });
    }

    private function generateRefundNumber(): string
    {
        do {
            $number = 'RFD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Refund::where('refund_number', $number)->exists());

        return $number;
    }

    private function generateTransactionCode(string $prefix = 'RFD'): string
    {
        do {
            $code = $prefix . '.' . now()->format('ymd') . '.' . strtoupper(Str::random(6));
        } while (FinanceTransaction::where('code', $code)->exists());

        return $code;
    }
}
