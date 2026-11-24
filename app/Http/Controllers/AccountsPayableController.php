<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayable;
use App\Services\AccountsPayableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountsPayableController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $query = AccountsPayable::query()
            ->with(['supplier', 'payments.payer']);

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', [$pattern])
                    ->orWhereHas('supplier', function ($sq) use ($pattern): void {
                        $sq->whereRaw('LOWER(name) LIKE ?', [$pattern]);
                    });
            });
        }

        if ($status !== '' && in_array($status, [
            AccountsPayable::STATUS_UNPAID,
            AccountsPayable::STATUS_PARTIAL,
            AccountsPayable::STATUS_PAID,
        ], true)) {
            $query->where('status', $status);
        }

        $payables = $query->latest('id')->paginate(15)->withQueryString();

        $summary = [
            'total_payable' => (float) AccountsPayable::query()->sum('total_amount'),
            'total_paid' => (float) AccountsPayable::query()->sum('paid_amount'),
            'total_due' => (float) AccountsPayable::query()->sum('balance_due'),
            'open_count' => (int) AccountsPayable::query()
                ->whereIn('status', [AccountsPayable::STATUS_UNPAID, AccountsPayable::STATUS_PARTIAL])
                ->count(),
        ];

        return view('finance.ap', compact('payables', 'summary', 'search', 'status'));
    }

    public function pay(
        Request $request,
        AccountsPayable $accountsPayable,
        AccountsPayableService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:40'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->recordPayment(
                $accountsPayable,
                $request->user(),
                (float) $validated['amount'],
                $validated['payment_method'],
                $validated['payment_date'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', "Pembayaran hutang invoice #{$accountsPayable->invoice_number} berhasil dicatat.");
    }
}
