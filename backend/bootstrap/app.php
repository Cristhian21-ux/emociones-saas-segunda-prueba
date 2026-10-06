<?php

use App\Http\Middleware\EnsureCentroActivo;
use App\Http\Middleware\EnsurePlanPermite;
use App\Http\Middleware\EnsureUserHasRole;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => EnsureUserHasRole::class,
            'plan' => EnsurePlanPermite::class,
            'centro.activo' => EnsureCentroActivo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // La API siempre responde JSON (también en 401/403/404).
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
    })->create();
