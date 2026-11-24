<?php

namespace App\Http\Controllers;

use App\Services\ShippingTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    public function show(string $token, ShippingTrackingService $service): View
    {
        $tracking = $service->publicTrackingPayload($token);

        return view('tracking.show', compact('tracking'));
    }

    public function api(string $token, ShippingTrackingService $service): JsonResponse
    {
        return response()->json($service->publicTrackingPayload($token));
    }
}
