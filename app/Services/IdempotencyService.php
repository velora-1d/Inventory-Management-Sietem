<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IdempotencyService
{
    /**
     * Execute a callback once for a given idempotency key and request payload.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function execute(
        string $key,
        string $route,
        array $payload,
        Closure $callback,
        ?int $actorId = null,
        int $ttlHours = 24
    ): array {
        $normalizedKey = trim($key);
        $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($normalizedKey, $route, $requestHash, $callback, $actorId, $ttlHours) {
            $existing = IdempotencyKey::where('key', $normalizedKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->expires_at && $existing->expires_at->isPast()) {
                    $existing->delete();
                } else {
                    if (!hash_equals($existing->request_hash, $requestHash)) {
                        throw ValidationException::withMessages([
                            'idempotency_key' => 'Idempotency key has already been used with a different payload.',
                        ]);
                    }

                    return $existing->response_payload ?? [];
                }
            }

            $result = (array) $callback();

            IdempotencyKey::create([
                'key' => $normalizedKey,
                'actor_id' => $actorId ?? Auth::id(),
                'route' => $route,
                'request_hash' => $requestHash,
                'status_code' => 200,
                'response_payload' => $result,
                'expires_at' => now()->addHours($ttlHours),
            ]);

            return $result;
        });
    }
}
