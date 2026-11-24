<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Record an immutable audit log entry.
     *
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?int $actorId = null
    ): AuditLog {
        $request = app()->bound('request') ? request() : null;

        return AuditLog::create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity ? (string) $entity->getKey() : null,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
