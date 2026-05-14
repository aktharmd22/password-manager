<?php

use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\IpWhitelist;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware — applied to every request.
        $middleware->append(SecurityHeaders::class);
        $middleware->append(ForceHttps::class);
        $middleware->append(IpWhitelist::class);

        // Route aliases.
        $middleware->alias([
            '2fa.enabled' => EnsureTwoFactorEnabled::class,
            '2fa.verified' => EnsureTwoFactorVerified::class,
            'log.activity' => LogActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
