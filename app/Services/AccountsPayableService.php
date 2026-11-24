<?php

namespace App\Services;

use App\Enums\FinanceCategoryType;
use App\Models\AccountsPayable;
use App\Models\AccountsPayablePayment;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountsPayableService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Create an Accounts Payable record from a supplier invoice (idempotent by supplier_invoice_id).
     */
    public function createFromSupplierInvoice($invoice): AccountsPayable
    {
        $invoiceId = $invoice->id ?? null;

        if ($invoiceId !== null) {
            $existing = AccountsPayable::query()
                ->where('supplier_invoice_id', $invoiceId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $supplierId = $invoice->supplier_id
            ?? $invoice->purchaseOrder?->supplier_id
            ?? $invoice->purchase?->supplier_id;

        if (! $supplierId) {
            throw new \DomainException('Supplier ID is required to create an accounts payable record.');
        }

        $amount = round((float) ($invoice->amount ?? $invoice->total_amount ?? $invoice->total ?? 0), 2);
        if ($amount <= 0) {
            throw new \DomainException('Supplier invoice amount must be greater than zero.');
        }

        $invoiceNumber = (string) ($invoice->invoice_number ?? ('INV-' . ($invoiceId ?? Str::upper(Str::random(6)))));

        return DB::transaction(function () use ($invoice, $invoiceId, $supplierId, $amount, $invoiceNumber): AccountsPayable {
            if ($invoiceId !== null) {
                $existing = AccountsPayable::query()
                    ->where('supplier_invoice_id', $invoiceId)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $ap = AccountsPayable::create([
                'supplier_id' => $supplierId,
                'supplier_invoice_id' => $invoiceId,
                'purchase_order_id' => $invoice->purchase_order_id ?? null,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $amount,
                'paid_amount' => 0,
                'balance_due' => $amount,
                'due_date' => $invoice->due_date ?? null,
                'status' => AccountsPayable::STATUS_UNPAID,
                'notes' => $invoice->notes ?? null,
            ]);

            $this->auditService->log(
                'ap.created',
                $ap,
                null,
                [
                    'invoice_number' => $ap->invoice_number,
                    'supplier_id' => $ap->supplier_id,
                    'total_amount' => $ap->total_amount,
                    'balance_due' => $ap->balance_due,
                    'status' => $ap->status,
                ]
            );

            return $ap;
        });
    }

    /**
     * Record a payment against an Accounts Payable record and log an expense transaction.
     */
    public function recordPayment(
        AccountsPayable $ap,
        User $actor,
        float $amount,
        string $paymentMethod,
        string $paymentDate,
        ?string $reference = null,
        ?string $notes = null
    ): AccountsPayablePayment {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \DomainException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($ap, $actor, $amount, $paymentMethod, $paymentDate, $reference, $notes): AccountsPayablePayment {
            $lockedAp = AccountsPayable::query()
                ->whereKey($ap->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = (float) $lockedAp->balance_due;
            if ($amount > $currentBalance + 0.001) {
                throw new \DomainException('Payment amount exceeds remaining balance due.');
            }

            $before = [
                'paid_amount' => $lockedAp->paid_amount,
                'balance_due' => $lockedAp->balance_due,
                'status' => $lockedAp->status,
            ];

            $categoryName = 'Pembayaran Hutang Supplier';
            $category = FinanceCategory::firstOrCreate(
                ['name' => $categoryName],
                [
                    'slug' => Str::slug($categoryName),
                    'type' => FinanceCategoryType::Expense,
                    'description' => 'Pembayaran hutang supplier (Accounts Payable)',
                ]
            );

            $description = 'Pembayaran Hutang Supplier #' . $lockedAp->invoice_number;
            if ($notes) {
                $description .= ' - ' . $notes;
            }

            $transaction = FinanceTransaction::create([
                'code' => $this->generateTransactionCode('AP'),
                'transaction_date' => $paymentDate,
                'finance_category_id' => $category->id,
                'amount' => (int) round($amount),
                'description' => $description,
                'external_reference' => $reference ?: $lockedAp->invoice_number,
                'reference_type' => AccountsPayable::class,
                'reference_id' => $lockedAp->id,
                'created_by' => $actor->id,
            ]);

            $payment = AccountsPayablePayment::create([
                'accounts_payable_id' => $lockedAp->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_date' => $paymentDate,
                'reference' => $reference,
                'finance_transaction_id' => $transaction->id,
                'paid_by' => $actor->id,
                'notes' => $notes,
            ]);

            $newPaid = round((float) $lockedAp->paid_amount + $amount, 2);
            $newBalance = round(max(0.0, (float) $lockedAp->total_amount - $newPaid), 2);
            $isFullyPaid = $newBalance <= 0.001;

            $lockedAp->update([
                'paid_amount' => $newPaid,
                'balance_due' => $isFullyPaid ? 0.00 : $newBalance,
                'status' => $isFullyPaid ? AccountsPayable::STATUS_PAID : AccountsPayable::STATUS_PARTIAL,
            ]);

            $this->auditService->log(
                'ap.payment_recorded',
                $lockedAp,
                $before,
                [
                    'paid_amount' => $lockedAp->paid_amount,
                    'balance_due' => $lockedAp->balance_due,
                    'status' => $lockedAp->status,
                    'payment_id' => $payment->id,
                    'payment_amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'finance_transaction_id' => $transaction->id,
                ],
                $notes,
                $actor->id
            );

            return $payment;
        });
    }

    private function generateTransactionCode(string $prefix = 'AP'): string
    {
        do {
            $code = $prefix . '.' . now()->format('ymd') . '.' . strtoupper(Str::random(6));
        } while (FinanceTransaction::where('code', $code)->exists());

        return $code;
    }
}
