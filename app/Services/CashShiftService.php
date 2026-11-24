<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\CashRegister;
use App\Models\CashShift;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashShiftService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function activeShiftForUser(User $user): ?CashShift
    {
        return CashShift::with('register')
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function defaultRegister(): CashRegister
    {
        return CashRegister::firstOrCreate(
            ['code' => 'REG-01'],
            [
                'name' => 'Kasir Utama (Register 1)',
                'location' => 'Front Counter',
                'is_active' => true,
            ]
        );
    }

    public function openShift(
        User $user,
        float $openingBalance,
        ?int $cashRegisterId = null,
        ?string $notes = null
    ): CashShift {
        return DB::transaction(function () use ($user, $openingBalance, $cashRegisterId, $notes) {
            if ($this->activeShiftForUser($user) !== null) {
                throw new \RuntimeException('Kasir masih memiliki shift yang sedang aktif.');
            }

            $register = $cashRegisterId !== null
                ? CashRegister::findOrFail($cashRegisterId)
                : $this->defaultRegister();

            $opening = round($openingBalance, 2);

            $shift = CashShift::create([
                'cash_register_id' => $register->id,
                'user_id' => $user->id,
                'opened_at' => now(),
                'opening_balance' => $opening,
                'expected_closing_balance' => $opening,
                'variance' => 0,
                'status' => 'open',
                'notes' => $notes,
            ]);

            $this->auditService->log(
                action: 'cash_shift.opened',
                entity: $shift,
                before: null,
                after: $shift->toArray(),
                reason: $notes,
                actorId: $user->id
            );

            return $shift->load('register');
        });
    }

    public function calculateExpectedCashTotal(CashShift $shift): float
    {
        $paymentCash = (float) SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.created_by', $shift->user_id)
            ->where('sales.status', SaleStatus::COMPLETED->value)
            ->where('sales.created_at', '>=', $shift->opened_at)
            ->where('sale_payments.payment_method', PaymentMethod::CASH->value)
            ->sum('sale_payments.amount');

        $legacyCashSales = Sale::query()
            ->where('created_by', $shift->user_id)
            ->where('status', SaleStatus::COMPLETED->value)
            ->where('payment_method', PaymentMethod::CASH->value)
            ->where('created_at', '>=', $shift->opened_at)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sale_payments')
                    ->whereColumn('sale_payments.sale_id', 'sales.id');
            })
            ->get(['total', 'cash_received', 'change']);

        $legacyCash = (float) $legacyCashSales->sum(function (Sale $sale) {
            if ((float) $sale->total > 0) {
                return (float) $sale->total;
            }

            return max(0.0, (float) $sale->cash_received - (float) $sale->change);
        });

        return round((float) $shift->opening_balance + $paymentCash + $legacyCash, 2);
    }

    public function closeShift(CashShift $shift, float $actualClosingBalance, ?string $notes = null): CashShift
    {
        return DB::transaction(function () use ($shift, $actualClosingBalance, $notes) {
            $lockedShift = CashShift::whereKey($shift->id)->lockForUpdate()->firstOrFail();

            if ($lockedShift->status === 'closed') {
                throw new \RuntimeException('Shift kasir ini sudah ditutup.');
            }

            $before = $lockedShift->toArray();
            $expected = $this->calculateExpectedCashTotal($lockedShift);
            $actual = round($actualClosingBalance, 2);
            $variance = round($actual - $expected, 2);

            $mergedNotes = $lockedShift->notes;
            if ($notes !== null && trim($notes) !== '') {
                $trimmed = trim($notes);
                $mergedNotes = $mergedNotes ? ($mergedNotes . "\n" . $trimmed) : $trimmed;
            }

            $lockedShift->update([
                'expected_closing_balance' => $expected,
                'actual_closing_balance' => $actual,
                'variance' => $variance,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $mergedNotes,
            ]);

            $freshShift = $lockedShift->fresh(['register', 'user']);

            $this->auditService->log(
                action: 'cash_shift.closed',
                entity: $freshShift,
                before: $before,
                after: $freshShift->toArray(),
                reason: $notes,
                actorId: Auth::id() ?? $lockedShift->user_id
            );

            return $freshShift;
        });
    }
}
