<?php

namespace App\Providers;

use App\Payments\PaymentGatewayManager;
use App\Support\SecurityRateLimiters;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Older libpq (e.g. shared hosting) lacks SNI support required by Neon; pass endpoint via PGOPTIONS.
        $pgHost = config('database.connections.pgsql.host');
        if (is_string($pgHost) && str_ends_with($pgHost, '.neon.tech') && empty(getenv('PGOPTIONS'))) {
            $endpoint = explode('.', $pgHost, 2)[0];
            if ($endpoint !== '') {
                putenv("PGOPTIONS=endpoint={$endpoint}");
            }
        }

        $this->app->singleton(PaymentGatewayManager::class, fn ($app) => new PaymentGatewayManager($app));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('money', function ($expression) {
            return "<?php echo format_money($expression); ?>";
        });

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'owner'])) {
                return true;
            }

            return null;
        });

        SecurityRateLimiters::register();
    }
}
