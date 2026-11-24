<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTrackingEvent;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShippingTrackingService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Record a timeline milestone for an order (idempotent per order_id + milestone_code + title).
     */
    public function recordMilestone(
        Order $order,
        string $milestoneCode,
        string $title,
        ?string $description = null,
        bool $isPublic = true,
        ?Carbon $occurredAt = null
    ): OrderTrackingEvent {
        $timestamp = $occurredAt ?? now();

        $event = OrderTrackingEvent::firstOrCreate(
            [
                'order_id' => $order->id,
                'milestone_code' => $milestoneCode,
                'title' => $title,
            ],
            [
                'description' => $description,
                'is_public' => $isPublic,
                'occurred_at' => $timestamp,
                'created_at' => now(),
            ]
        );

        if ($description !== null && $event->description !== $description) {
            $event->description = $description;
            $event->save();
        }

        return $event;
    }

    /**
     * Create or update a shipment and mark the order as shipped.
     *
     * @param array<string, mixed> $data
     */
    public function dispatchShipment(Order $order, array $data, User $actor): Shipment
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $existing = Shipment::where('order_id', $lockedOrder->id)
                ->latest('id')
                ->first();

            $before = $existing?->toArray();

            $estimatedDeliveryAt = ! empty($data['estimated_delivery_at'])
                ? Carbon::parse($data['estimated_delivery_at'])
                : null;

            $attributes = [
                'order_id' => $lockedOrder->id,
                'carrier' => $data['carrier'],
                'service' => $data['service'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'status' => 'shipped',
                'shipped_at' => now(),
                'estimated_delivery_at' => $estimatedDeliveryAt,
                'notes' => $data['notes'] ?? null,
            ];

            if ($existing) {
                $existing->update($attributes);
                $shipment = $existing->fresh();
            } else {
                $shipment = Shipment::create($attributes);
            }

            $lockedOrder->shipment_status = 'shipped';
            $lockedOrder->status = 'shipped';
            $lockedOrder->save();

            ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => 'shipped',
                'description' => "Dikirim via {$shipment->carrier}",
                'occurred_at' => now(),
                'created_at' => now(),
            ]);

            $this->recordMilestone(
                order: $lockedOrder,
                milestoneCode: 'shipped',
                title: 'Pesanan dikirim',
                description: "Resi: {$shipment->tracking_number}",
                isPublic: true
            );

            $this->auditService->log(
                action: 'shipment.dispatched',
                entity: $shipment,
                before: $before,
                after: $shipment->toArray(),
                reason: $data['notes'] ?? null,
                actorId: $actor->id
            );

            return $shipment->load(['order', 'events']);
        });
    }

    /**
     * Confirm shipment delivery (by customer or admin) with optional Proof of Delivery upload.
     */
    public function confirmDelivery(
        Shipment $shipment,
        User $actor,
        string $source = 'customer',
        ?UploadedFile $proof = null,
        ?string $notes = null
    ): Shipment {
        return DB::transaction(function () use ($shipment, $actor, $source, $proof, $notes) {
            $lockedShipment = Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            $before = $lockedShipment->toArray();

            $proofPath = $lockedShipment->proof_of_delivery_path;
            if ($proof !== null) {
                $proofPath = Storage::disk('local')->putFile('shipment-pods', $proof);
            }

            $mergedNotes = $notes !== null && trim($notes) !== ''
                ? trim($notes)
                : $lockedShipment->notes;

            $lockedShipment->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'confirmed_by' => $actor->id,
                'confirmation_source' => $source,
                'proof_of_delivery_path' => $proofPath,
                'notes' => $mergedNotes,
            ]);

            ShipmentEvent::create([
                'shipment_id' => $lockedShipment->id,
                'status' => 'delivered',
                'description' => 'Paket diterima',
                'occurred_at' => now(),
                'created_at' => now(),
            ]);

            $order = Order::whereKey($lockedShipment->order_id)->lockForUpdate()->first();
            if ($order !== null) {
                $order->shipment_status = 'delivered';
                $order->status = 'completed';
                $order->completed_at = now();
                $order->save();

                $this->recordMilestone(
                    order: $order,
                    milestoneCode: 'delivered',
                    title: 'Pesanan diterima',
                    description: 'Paket telah diterima dan dikonfirmasi.',
                    isPublic: true
                );

                $this->recordMilestone(
                    order: $order,
                    milestoneCode: 'completed',
                    title: 'Pesanan selesai',
                    description: 'Transaksi dan pengiriman telah selesai.',
                    isPublic: true
                );
            }

            $freshShipment = $lockedShipment->fresh(['order', 'events', 'confirmer']);

            $this->auditService->log(
                action: 'shipment.delivered',
                entity: $freshShipment,
                before: $before,
                after: $freshShipment->toArray(),
                reason: $notes,
                actorId: $actor->id
            );

            return $freshShipment;
        });
    }

    /**
     * Build sanitized public tracking payload with strict PII guard (Blueprint §8).
     *
     * @return array<string, mixed>
     */
    public function publicTrackingPayload(string $token): array
    {
        $order = Order::where('public_tracking_token', $token)->firstOrFail();

        $shipment = Shipment::where('order_id', $order->id)
            ->latest('id')
            ->first();

        $milestones = OrderTrackingEvent::where('order_id', $order->id)
            ->where('is_public', true)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (OrderTrackingEvent $event): array => [
                'milestone_code' => $event->milestone_code,
                'title' => $event->title,
                'description' => $event->description,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $isShipped = $shipment !== null && in_array($shipment->status, ['shipped', 'in_transit', 'delivered'], true);

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'delivery_type' => $order->delivery_type,
            'shipment_status' => $order->shipment_status,
            'carrier' => $isShipped ? $shipment->carrier : null,
            'service' => $isShipped ? $shipment->service : null,
            'tracking_number' => $isShipped ? $shipment->tracking_number : null,
            'estimated_delivery_at' => $shipment?->estimated_delivery_at?->toIso8601String(),
            'delivered_at' => $shipment?->delivered_at?->toIso8601String(),
            'milestones' => $milestones,
        ];
    }
}
