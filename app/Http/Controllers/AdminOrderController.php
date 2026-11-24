<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CustomerOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $paymentStatus = (string) $request->input('payment_status', '');

        $query = Order::query()->with(['user', 'customer', 'items', 'latestPayment']);

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(order_number) LIKE ?', [$pattern])
                    ->orWhereHas('user', function ($uq) use ($pattern): void {
                        $uq->whereRaw('LOWER(name) LIKE ?', [$pattern])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$pattern]);
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($paymentStatus !== '') {
            $query->where('payment_status', $paymentStatus);
        }

        $orders = $query->latest('id')->paginate(15)->withQueryString();

        $summary = [
            'total_orders' => Order::query()->count(),
            'pending_payment' => Order::query()->where('status', 'pending_payment')->count(),
            'waiting_verification' => Order::query()->where('payment_status', 'waiting_verification')->count(),
            'confirmed_active' => Order::query()->whereIn('status', ['confirmed', 'processing', 'ready_to_ship', 'shipped'])->count(),
        ];

        return view('orders.index', compact('orders', 'summary', 'search', 'status', 'paymentStatus'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'customer', 'address', 'items.product', 'payments.verifier', 'statusHistories.changer']);

        if (class_exists(\App\Models\OrderTask::class)) {
            $order->load('tasks');
        }

        if (class_exists(\App\Models\Shipment::class)) {
            $order->load('shipment');
        }

        if (class_exists(\App\Models\OrderTrackingEvent::class)) {
            $order->load('trackingEvents');
        }

        return view('orders.show', compact('order'));
    }

    public function verifyPayment(Request $request, Order $order, CustomerOrderService $service): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $approve = $validated['decision'] === 'approve';
        $service->verifyPayment($order, $request->user(), $approve, $validated['reason'] ?? null);

        return back()->with(
            'success',
            $approve
                ? "Pembayaran pesanan #{$order->order_number} berhasil diverifikasi."
                : "Pembayaran pesanan #{$order->order_number} ditolak."
        );
    }

    public function cancel(Request $request, Order $order, CustomerOrderService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $service->cancelOrder($order, $request->user(), $validated['reason']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pesanan #{$order->order_number} berhasil dibatalkan.");
    }
}
