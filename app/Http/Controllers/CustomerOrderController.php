<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\CustomerOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->with(['items', 'latestPayment', 'address'])
            ->latest('id')
            ->paginate(10);

        $addresses = CustomerAddress::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        return view('my-orders.index', compact('orders', 'addresses'));
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['items.product', 'payments.verifier', 'address', 'statusHistories.changer']);

        if (class_exists(\App\Models\Shipment::class)) {
            $order->load('shipment');
        }

        if (class_exists(\App\Models\OrderTrackingEvent::class)) {
            $order->load('trackingEvents');
        }

        $rawPhone = preg_replace('/\D+/', '', brand_whatsapp_admin()) ?? '';
        if (str_starts_with($rawPhone, '0')) {
            $rawPhone = '62' . substr($rawPhone, 1);
        }

        $waMessage = urlencode("Halo Admin " . brand_name() . ", saya ingin konfirmasi pesanan #{$order->order_number} (Total: " . format_money($order->total) . ").");
        $whatsappUrl = $rawPhone !== '' ? "https://wa.me/{$rawPhone}?text={$waMessage}" : null;

        $trackingUrl = Route::has('tracking.show')
            ? route('tracking.show', $order->public_tracking_token)
            : url('/tracking/' . $order->public_tracking_token);

        return view('my-orders.show', compact('order', 'whatsappUrl', 'trackingUrl'));
    }

    public function uploadProof(Request $request, Order $order, CustomerOrderService $service): RedirectResponse
    {
        Gate::authorize('uploadProof', $order);

        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $service->uploadPaymentProof($order, $request->file('proof'), $request->user());

        return back()->with('success', 'Bukti pembayaran berhasil diunggah dan menunggu verifikasi admin.');
    }

    public function cancel(Request $request, Order $order, CustomerOrderService $service): RedirectResponse
    {
        Gate::authorize('cancel', $order);

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

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:50'],
            'address_line' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $customer = Customer::query()
            ->when($user->email, fn ($q) => $q->where('email', $user->email))
            ->first();

        $isDefault = (bool) ($validated['is_default'] ?? false);
        if ($isDefault) {
            CustomerAddress::query()
                ->where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        CustomerAddress::query()->create([
            'user_id' => $user->id,
            'customer_id' => $customer?->id,
            'label' => $validated['label'],
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'is_default' => $isDefault,
        ]);

        return back()->with('success', 'Alamat pengiriman berhasil disimpan.');
    }
}
