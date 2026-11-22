<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('mcp', function (Request $request): Limit {
            $perMinute = max(1, (int) config('harbor.rate_limit_per_minute'));

            return Limit::perMinute($perMinute)->by(
                (string) ($request->user()?->getAuthIdentifier() ?: $request->ip())
            );
        });
    }
}
