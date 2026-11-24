<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'key',
        'actor_id',
        'route',
        'request_hash',
        'status_code',
        'response_payload',
        'expires_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
