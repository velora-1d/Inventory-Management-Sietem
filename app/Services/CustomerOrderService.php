<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerOrderService
{
    public function __construct(
        private readonly InventoryLedgerService $inventoryLedger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Place a new customer order inside a database transaction with stock reservation.
     *
     * @param array<int, array{product_id: int|string, quantity: int|string}> $items
     * @param array<string, mixed> $checkoutData
     */
    public function placeOrder(User $user, array $items, array $checkoutData): Order
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Order must contain at least one item.');
        }

        $normalizedItems = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $qty < 1) {
                throw new \InvalidArgumentException('Each order item must have a valid product and quantity of at least 1.');
            }

            $normalizedItems[$productId] = ($normalizedItems[$productId] ?? 0) + $qty;
        }

        return DB::transaction(function () use ($user, $normalizedItems, $checkoutData): Order {
            $customer = $this->resolveOrCreateCustomer($user, $checkoutData);

            $deliveryType = (string) ($checkoutData['delivery_type'] ?? 'delivery');
            if (! in_array($deliveryType, ['pickup', 'delivery'], true)) {
                $deliveryType = 'delivery';
            }

            $addressId = $this->resolveOrCreateAddressId($user, $customer, $deliveryType, $checkoutData);

            $subtotal = 0.0;
            $preparedLines = [];

            foreach ($normalizedItems as $productId => $qty) {
                $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();

                if (! $product->is_active) {
                    throw new \RuntimeException("Product [{$product->name}] is currently unavailable.");
                }

                $available = $this->inventoryLedger->availableStock($product->id);
                if ($available < $qty) {
                    throw new \RuntimeException("Insufficient available stock for [{$product->name}]. Available: {$available}, requested: {$qty}.");
                }

                $unitPrice = (float) $product->selling_price;
                $costPrice = (float) $product->purchase_price;
                $lineSubtotal = round($unitPrice * $qty, 2);
                $subtotal += $lineSubtotal;

                $preparedLines[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'cost_price' => $costPrice,
                    'unit_price' => $unitPrice,
                    'discount' => 0.0,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $shippingCost = $deliveryType === 'delivery'
                ? max(0.0, (float) ($checkoutData['shipping_cost'] ?? 0))
                : 0.0;
            $total = max(0.0, round($subtotal - $discountTotal + $taxTotal + $shippingCost, 2));

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'public_tracking_token' => $this->generateTrackingToken(),
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'customer_address_id' => $addressId,
                'status' => 'pending_payment',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'pending',
                'shipment_status' => 'unshipped',
                'delivery_type' => $deliveryType,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'notes' => $checkoutData['notes'] ?? null,
            ]);

            foreach ($preparedLines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $line['quantity'],
                    'cost_price' => $line['cost_price'],
                    'unit_price' => $line['unit_price'],
                    'discount' => $line['discount'],
                    'subtotal' => $line['subtotal'],
                ]);

                $this->inventoryLedger->reserveStock(
                    productId: $product->id,
                    qty: $line['quantity'],
                    referenceType: 'order',
                    referenceId: $order->id,
                    reason: "Order #{$order->order_number}",
                    orderId: $order->id,
                    actorId: $user->id
                );
            }

            OrderPayment::query()->create([
                'order_id' => $order->id,
                'payment_method' => (string) ($checkoutData['payment_method'] ?? 'manual_transfer'),
                'gateway' => (string) ($checkoutData['gateway'] ?? 'manual'),
                'amount' => $total,
                'status' => 'pending',
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending_payment',
                'changed_by' => $user->id,
                'reason' => 'Order placed by customer',
                'created_at' => now(),
            ]);

            if (class_exists(\App\Services\ShippingTrackingService::class)) {
                app(\App\Services\ShippingTrackingService::class)->recordMilestone(
                    $order,
                    'order_received',
                    'Pesanan diterima',
                    'Pesanan telah dibuat dan menunggu pembayaran.',
                    true
                );
            }

            $this->auditService->log(
                action: 'order.placed',
                entity: $order,
                before: null,
                after: $order->toArray(),
                reason: 'Customer order placed',
                actorId: $user->id
            );

            return $order->fresh(['items', 'payments', 'latestPayment', 'address', 'statusHistories']);
        });
    }

    /**
     * Store a customer payment proof file on the private local disk and update payment status.
     */
    public function uploadPaymentProof(Order $order, UploadedFile $file, User $actor): OrderPayment
    {
        return DB::transaction(function () use ($order, $file, $actor): OrderPayment {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $before = $lockedOrder->toArray();

            $path = Storage::disk('local')->putFile('payment-proofs', $file);

            $payment = $lockedOrder->payments()->latest('id')->first();
            if (! $payment) {
                $payment = OrderPayment::query()->create([
                    'order_id' => $lockedOrder->id,
                    'payment_method' => 'manual_transfer',
                    'gateway' => 'manual',
                    'amount' => $lockedOrder->total,
                    'proof_path' => $path,
                    'status' => 'submitted',
                ]);
            } else {
                $payment->update([
                    'proof_path' => $path,
                    'status' => 'submitted',
                ]);
            }

            $lockedOrder->update([
                'payment_status' => 'waiting_verification',
            ]);

            $this->auditService->log(
                action: 'order.payment_proof_uploaded',
                entity: $lockedOrder,
                before: $before,
                after: $lockedOrder->fresh()->toArray(),
                reason: 'Customer uploaded payment proof',
                actorId: $actor->id
            );

            return $payment->fresh();
        });
    }

    /**
     * Approve or reject the latest payment for an order.
     */
    public function verifyPayment(Order $order, User $verifier, bool $approve = true, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $verifier, $approve, $reason): Order {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $before = $lockedOrder->toArray();

            $payment = $lockedOrder->payments()->latest('id')->first();
            if (! $payment) {
                $payment = OrderPayment::query()->create([
                    'order_id' => $lockedOrder->id,
                    'payment_method' => 'manual_transfer',
                    'gateway' => 'manual',
                    'amount' => $lockedOrder->total,
                    'status' => 'pending',
                ]);
            }

            $payment->update([
                'status' => $approve ? 'verified' : 'rejected',
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'notes' => $reason,
            ]);

            if ($approve) {
                $fromStatus = $lockedOrder->status;

                $lockedOrder->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                OrderStatusHistory::query()->create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => $fromStatus,
                    'to_status' => 'confirmed',
                    'changed_by' => $verifier->id,
                    'reason' => $reason ?: 'Payment verified',
                    'created_at' => now(),
                ]);

                $this->auditService->log(
                    action: 'order.payment_verified',
                    entity: $lockedOrder,
                    before: $before,
                    after: $lockedOrder->fresh()->toArray(),
                    reason: $reason ?: 'Payment verified',
                    actorId: $verifier->id
                );

                if (class_exists(\App\Services\ShippingTrackingService::class)) {
                    app(\App\Services\ShippingTrackingService::class)->recordMilestone(
                        $lockedOrder,
                        'payment_confirmed',
                        'Pembayaran dikonfirmasi',
                        'Pembayaran telah diverifikasi dan pesanan dikonfirmasi.',
                        true
                    );
                }

                if (class_exists(\App\Services\WorkManagementService::class)) {
                    app(\App\Services\WorkManagementService::class)->generateTasksForOrder($lockedOrder);
                }
            } else {
                $lockedOrder->update([
                    'payment_status' => 'failed',
                ]);

                $this->auditService->log(
                    action: 'order.payment_rejected',
                    entity: $lockedOrder,
                    before: $before,
                    after: $lockedOrder->fresh()->toArray(),
                    reason: $reason ?: 'Payment rejected',
                    actorId: $verifier->id
                );
            }

            return $lockedOrder->fresh(['items', 'payments', 'latestPayment', 'statusHistories']);
        });
    }

    /**
     * Cancel an order before processing/fulfillment and release all active stock reservations.
     */
    public function cancelOrder(Order $order, User $actor, string $reason): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason): Order {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->isCancellable()) {
                throw new \DomainException("Order #{$lockedOrder->order_number} cannot be cancelled in status [{$lockedOrder->status}].");
            }

            $before = $lockedOrder->toArray();
            $fromStatus = $lockedOrder->status;

            $reservations = StockReservation::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->get();

            foreach ($reservations as $reservation) {
                $this->inventoryLedger->releaseReservation(
                    reservation: $reservation,
                    reason: "Order #{$lockedOrder->order_number} cancelled: {$reason}",
                    actorId: $actor->id
                );
            }

            $lockedOrder->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $lockedOrder->id,
                'from_status' => $fromStatus,
                'to_status' => 'cancelled',
                'changed_by' => $actor->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $this->auditService->log(
                action: 'order.cancelled',
                entity: $lockedOrder,
                before: $before,
                after: $lockedOrder->fresh()->toArray(),
                reason: $reason,
                actorId: $actor->id
            );

            return $lockedOrder->fresh(['items', 'payments', 'latestPayment', 'statusHistories']);
        });
    }

    /**
     * Find an existing customer record for the user or create one.
     *
     * @param array<string, mixed> $checkoutData
     */
    private function resolveOrCreateCustomer(User $user, array $checkoutData): Customer
    {
        $customer = Customer::query()
            ->when($user->email, fn ($query) => $query->where('email', $user->email))
            ->when(! $user->email, fn ($query) => $query->where('name', $user->name))
            ->first();

        if ($customer) {
            return $customer;
        }

        return Customer::query()->create([
            'name' => $checkoutData['recipient_name'] ?? $user->name,
            'email' => $user->email,
            'phone' => $checkoutData['phone'] ?? null,
            'address' => $checkoutData['address_line'] ?? null,
        ]);
    }

    /**
     * Resolve or create a shipping address for delivery orders.
     *
     * @param array<string, mixed> $checkoutData
     */
    private function resolveOrCreateAddressId(User $user, Customer $customer, string $deliveryType, array $checkoutData): ?int
    {
        if ($deliveryType !== 'delivery') {
            return null;
        }

        if (! empty($checkoutData['customer_address_id'])) {
            $address = CustomerAddress::query()
                ->where('id', (int) $checkoutData['customer_address_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            return $address->id;
        }

        if (! empty($checkoutData['address_line']) && ! empty($checkoutData['recipient_name'])) {
            $address = CustomerAddress::query()->create([
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'label' => $checkoutData['address_label'] ?? 'Primary',
                'recipient_name' => $checkoutData['recipient_name'],
                'phone' => $checkoutData['phone'] ?? ($customer->phone ?? '-'),
                'address_line' => $checkoutData['address_line'],
                'city' => $checkoutData['city'] ?? '-',
                'province' => $checkoutData['province'] ?? null,
                'postal_code' => $checkoutData['postal_code'] ?? null,
                'is_default' => true,
            ]);

            return $address->id;
        }

        return null;
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('ymd') . '-';

        do {
            $candidate = $prefix . strtoupper(Str::random(4));
        } while (Order::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    private function generateTrackingToken(): string
    {
        do {
            $token = Str::random(48);
        } while (Order::query()->where('public_tracking_token', $token)->exists());

        return $token;
    }
}
