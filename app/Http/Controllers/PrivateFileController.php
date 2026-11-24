<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use App\Models\Shipment;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PrivateFileController extends Controller
{
    /**
     * Stream a customer payment proof file to the owning customer or authorized staff.
     */
    public function orderPaymentProof(Request $request, OrderPayment $payment): Response
    {
        $user = $request->user();
        $isOwner = $user !== null
            && $payment->order?->user_id !== null
            && $payment->order->user_id === $user->id;
        $isAuthorizedStaff = $user !== null
            && ! $user->hasRole('customer')
            && ($user->can('orders.view') || $user->can('finance.verify_payment'));

        if (! $isOwner && ! $isAuthorizedStaff) {
            abort(403);
        }

        if (! $payment->proof_path || ! Storage::disk('local')->exists($payment->proof_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($payment->proof_path);
    }

    /**
     * Stream an internal work task attachment to staff with work permissions.
     */
    public function taskAttachment(Request $request, TaskAttachment $attachment): Response
    {
        $user = $request->user();

        if (
            $user === null
            || (! $user->can('work.view') && ! $user->can('work.manage') && ! $user->can('work.qc'))
        ) {
            abort(403);
        }

        $disk = $attachment->disk ?: 'local';

        if (! $attachment->path || ! Storage::disk($disk)->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk($disk)->response($attachment->path, $attachment->original_name ?: null);
    }

    /**
     * Stream a shipment Proof of Delivery file to the owning customer or authorized staff.
     */
    public function shipmentPod(Request $request, Shipment $shipment): Response
    {
        $user = $request->user();
        $isOwner = $user !== null
            && $shipment->order?->user_id !== null
            && $shipment->order->user_id === $user->id;
        $isAuthorizedStaff = $user !== null
            && ! $user->hasRole('customer')
            && ($user->can('shipping.view') || $user->can('orders.view'));

        if (! $isOwner && ! $isAuthorizedStaff) {
            abort(403);
        }

        if (! $shipment->proof_of_delivery_path || ! Storage::disk('local')->exists($shipment->proof_of_delivery_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($shipment->proof_of_delivery_path);
    }
}
