<?php

namespace App\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class SecurityRateLimiters
{
    /**
     * Register security-sensitive named rate limiters for auth, checkout,
     * public order tracking, and payment webhook endpoints.
     */
    public static function register(): void
    {
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by(strtolower((string) $r->input('email', '')) . '|' . $r->ip()));
        RateLimiter::for('register', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('public-tracking', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
        RateLimiter::for('payment-webhook', fn (Request $r) => Limit::perMinute(120)->by($r->ip()));
    }
}
