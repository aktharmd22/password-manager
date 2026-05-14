<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block authenticated users from app routes until they've completed
 * 2FA verification for the current session. The session flag
 * `vault.2fa_verified` is set by TwoFactorController@verify after a
 * valid TOTP or recovery code submission.
 *
 * Users without 2FA enabled are passed through to EnsureTwoFactorEnabled,
 * which redirects them to the setup flow.
 */
class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasTwoFactorEnabled() && ! $request->session()->get('vault.2fa_verified', false)) {
            $allowed = [
                'two-factor.verify',
                'two-factor.verify.store',
                'logout',
            ];

            if (! $request->routeIs($allowed)) {
                return redirect()->route('two-factor.verify');
            }
        }

        return $next($request);
    }
}
