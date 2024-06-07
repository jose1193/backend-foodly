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
       $middleware->alias([
            'check.permission' => \App\Http\Middleware\CheckPermission::class,
            'json_throttle' => \App\Http\Middleware\JsonThrottleMiddleware::class,
            'auth.routes' => \App\Http\Middleware\AuthenticateRoutes::class,
            'handle.notfound' => \App\Http\Middleware\Json404Middleware::class,
            'token.auth' => \App\Http\Middleware\TokenAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
