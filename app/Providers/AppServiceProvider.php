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
        // Configure rate limiters for authentication endpoints
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return errorJson(
                        'Too many login attempts. Please try again later.',
                        null,
                        429
                    )->withHeaders($headers);
                });
        });

        RateLimiter::for('invitations', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return errorJson(
                        'Too many invitation requests. Please try again later.',
                        null,
                        429
                    )->withHeaders($headers);
                });
        });
    }
}
