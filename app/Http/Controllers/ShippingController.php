<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\ShippingTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->whereIn('status', ['confirmed', 'processing', 'ready_to_ship', 'shipped', 'delivered', 'completed'])
            ->latest('id')
            ->paginate(15, ['*'], 'orders_page');

        $shipmentsByOrderId = Shipment::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->latest('id')
            ->get()
            ->keyBy('order_id');

        $shipments = Shipment::with(['order', 'events', 'confirmer'])
            ->latest('id')
            ->paginate(15, ['*'], 'shipments_page');

        return view('shipping.index', compact('orders', 'shipments', 'shipmentsByOrderId'));
    }

    public function dispatch(Request $request, Order $order, ShippingTrackingService $service): RedirectResponse
    {
        $validated = $request->validate([
            'carrier' => ['required', 'string', 'max:100'],
            'service' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['required', 'string', 'max:120'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service->dispatchShipment($order, $validated, $request->user());

        return back()->with(
            'success',
            'Pengiriman untuk pesanan ' . ($order->order_number ?? ('#' . $order->id)) . ' berhasil dicatat.'
        );
    }

    public function confirmDelivery(Request $request, Shipment $shipment, ShippingTrackingService $service): RedirectResponse
    {
        $user = $request->user();
        $isOwner = $shipment->order?->user_id !== null && $shipment->order->user_id === $user->id;

        if (! $isOwner && ! $user->can('shipping.manage') && ! $user->can('orders.manage')) {
            abort(403);
        }

        $validated = $request->validate([
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $source = $isOwner ? 'customer' : 'admin';

        $service->confirmDelivery(
            shipment: $shipment,
            actor: $user,
            source: $source,
            proof: $request->file('proof'),
            notes: $validated['notes'] ?? null
        );

        return back()->with('success', 'Konfirmasi penerimaan paket berhasil disimpan.');
    }
}
