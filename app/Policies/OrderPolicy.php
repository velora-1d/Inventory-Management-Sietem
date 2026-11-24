<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        if ((int) $order->user_id === (int) $user->id) {
            return true;
        }

        if ($user->can('orders.manage')) {
            return true;
        }

        return ! $user->hasRole('customer') && $user->can('orders.view');
    }

    /**
     * Determine whether the user can upload payment proof for the order.
     */
    public function uploadProof(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->id || $user->can('orders.manage');
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->id || $user->can('orders.manage');
    }

    /**
     * Determine whether the user can confirm delivery for the order.
     */
    public function confirmDelivery(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->id
            || $user->can('shipping.manage')
            || $user->can('orders.manage');
    }
}
