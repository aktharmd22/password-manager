<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('vault.force_https') && ! $request->isSecure() && ! app()->environment('local', 'testing')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
