<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CashShift;
use App\Services\CashShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashShiftController extends Controller
{
    public function index(Request $request, CashShiftService $service): View
    {
        $user = $request->user();
        $activeShift = $service->activeShiftForUser($user);
        $expectedCash = $activeShift ? $service->calculateExpectedCashTotal($activeShift) : null;
        $registers = CashRegister::where('is_active', true)->orderBy('name')->get();

        if ($registers->isEmpty()) {
            $service->defaultRegister();
            $registers = CashRegister::where('is_active', true)->orderBy('name')->get();
        }

        $shifts = CashShift::with(['register', 'user'])
            ->latest('id')
            ->paginate(15);

        return view('pos.shifts', compact('activeShift', 'expectedCash', 'registers', 'shifts'));
    }

    public function open(Request $request, CashShiftService $service): RedirectResponse
    {
        $validated = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'cash_register_id' => ['nullable', 'exists:cash_registers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $shift = $service->openShift(
                user: $request->user(),
                openingBalance: (float) $validated['opening_balance'],
                cashRegisterId: isset($validated['cash_register_id']) ? (int) $validated['cash_register_id'] : null,
                notes: $validated['notes'] ?? null
            );

            return back()->with(
                'success',
                'Shift kasir berhasil dibuka pada ' . ($shift->register->name ?? 'Register') . ' dengan saldo awal ' . format_money($shift->opening_balance) . '.'
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function close(Request $request, CashShift $shift, CashShiftService $service): RedirectResponse
    {
        $user = $request->user();

        if (
            $shift->user_id !== $user->id
            && !$user->can('reports.view')
            && !$user->can('pos.manage')
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'actual_closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $closedShift = $service->closeShift(
                shift: $shift,
                actualClosingBalance: (float) $validated['actual_closing_balance'],
                notes: $validated['notes'] ?? null
            );

            return back()->with(
                'success',
                sprintf(
                    'Shift kasir ditutup. Ekspektasi: %s | Aktual: %s | Selisih: %s',
                    format_money($closedShift->expected_closing_balance),
                    format_money($closedShift->actual_closing_balance),
                    format_money($closedShift->variance)
                )
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
