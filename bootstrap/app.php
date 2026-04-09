<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Security headers (HSTS, X-Frame, etc.)
        $middleware->prepend(\App\Http\Middleware\SecurityHeaders::class);

        // Trust all proxies (reverse proxy)
        $middleware->trustProxies(at: '*');

        // Activer les sessions pour les routes API (Sanctum SPA)
        $middleware->statefulApi();

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\CheckAccountStatus::class,
        ]);

        // Rate limiting personnalisé
        $middleware->alias([
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'two-factor' => \App\Http\Middleware\EnsureTwoFactorAuthenticated::class,
            'not-readonly' => \App\Http\Middleware\CheckNotReadOnly::class,
            'commune' => \App\Http\Middleware\ResolveCommuneSubdomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
