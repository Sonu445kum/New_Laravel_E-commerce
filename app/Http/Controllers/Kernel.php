<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware stack.
     *
     * These middleware run during every request to your application.
     */
    protected $middleware = [
        // Handles trusted proxies
        \App\Http\Middleware\TrustProxies::class,

        // Handles CORS (Cross-Origin Resource Sharing)
        \Illuminate\Http\Middleware\HandleCors::class,

        // Checks if the application is in maintenance mode
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,

        // Validates POST request size
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,

        // Trims strings from request data
        \App\Http\Middleware\TrimStrings::class,

        // Converts empty strings to null
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * Route middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,

            // Enables authentication errors to be flashed to session
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            // CSRF Protection
            \App\Http\Middleware\VerifyCsrfToken::class,

            // Routes using the web middleware group will automatically get these
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // Throttle API calls (60 per minute by default)
            'throttle:api',

            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Middleware aliases.
     *
     * These can be used in routes/web.php for quick reference.
     */
    protected $middlewareAliases = [
        // Default auth middleware
        'auth' => \App\Http\Middleware\Authenticate::class,

        // Redirect if already authenticated
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,

        // Basic HTTP auth
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,

        // Ensure email is verified
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // Role-based admin middleware (custom)
        'admin' => \App\Http\Middleware\IsAdmin::class,
    ];
}

