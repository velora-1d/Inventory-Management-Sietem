<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Refund;
use App\Models\Sale;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $query = Refund::query()
            ->with(['order', 'sale', 'requester', 'processor']);

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(refund_number) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(reason) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE(reference, \'\')) LIKE ?', [$pattern]);
            });
        }

        if ($status !== '' && in_array($status, [
            Refund::STATUS_REQUESTED,
            Refund::STATUS_APPROVED,
            Refund::STATUS_PROCESSED,
            Refund::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $status);
        }

        $refunds = $query->latest('id')->paginate(15)->withQueryString();

        $summary = [
            'total_refunds' => (int) Refund::query()->count(),
            'requested_count' => (int) Refund::query()->where('status', Refund::STATUS_REQUESTED)->count(),
            'approved_count' => (int) Refund::query()->where('status', Refund::STATUS_APPROVED)->count(),
            'processed_total' => (float) Refund::query()->where('status', Refund::STATUS_PROCESSED)->sum('amount'),
        ];

        $recentOrders = Order::query()->latest('id')->limit(25)->get(['id', 'order_number', 'total']);
        $recentSales = Sale::query()->latest('id')->limit(25)->get(['id', 'invoice_number', 'total']);

        return view('finance.refunds', compact(
            'refunds',
            'summary',
            'search',
            'status',
            'recentOrders',
            'recentSales'
        ));
    }

    public function store(Request $request, RefundService $service): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:40'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
        ]);

        try {
            $refund = $service->requestRefund(
                $request->user(),
                (float) $validated['amount'],
                $validated['method'],
                $validated['reason'],
                isset($validated['order_id']) ? (int) $validated['order_id'] : null,
                isset($validated['sale_id']) ? (int) $validated['sale_id'] : null
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', "Pengajuan refund #{$refund->refund_number} berhasil dibuat.");
    }

    public function approve(Request $request, Refund $refund, RefundService $service): RedirectResponse
    {
        try {
            $service->approveRefund($refund, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Refund #{$refund->refund_number} disetujui.");
    }

    public function process(Request $request, Refund $refund, RefundService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $service->processRefund($refund, $request->user(), $validated['reference'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Refund #{$refund->refund_number} berhasil diproses.");
    }

    public function reject(Request $request, Refund $refund, RefundService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $service->rejectRefund($refund, $request->user(), $validated['reason']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Refund #{$refund->refund_number} ditolak.");
    }
}
