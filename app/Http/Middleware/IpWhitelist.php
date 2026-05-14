<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional IP allow-list. Disabled by default. Enable by setting
 *   VAULT_IP_WHITELIST_ENABLED=true
 *   VAULT_IP_WHITELIST="203.0.113.5,203.0.113.6"
 * Supports plain IPs only — adapt to CIDR if needed.
 */
class IpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $config = config('vault.ip_whitelist');

        if (! ($config['enabled'] ?? false)) {
            return $next($request);
        }

        $allowed = $config['allowed'] ?? [];
        if (empty($allowed)) {
            return $next($request);
        }

        if (! in_array($request->ip(), $allowed, true)) {
            abort(403, 'Access from your network is not permitted.');
        }

        return $next($request);
    }
}
