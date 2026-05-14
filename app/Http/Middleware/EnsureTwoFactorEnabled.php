<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force any authenticated user without 2FA enabled to complete 2FA setup
 * before they can use the rest of the application.
 *
 * This is a SINGLE-USER application so we treat 2FA as mandatory, not optional.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasTwoFactorEnabled()) {
            // Don't loop: setup/verify routes already allow this state.
            $allowed = [
                'two-factor.setup',
                'two-factor.setup.store',
                'two-factor.recovery-codes',
                'two-factor.recovery-codes.acknowledge',
                'logout',
            ];

            if (! $request->routeIs($allowed)) {
                return redirect()->route('two-factor.setup');
            }
        }

        return $next($request);
    }
}
