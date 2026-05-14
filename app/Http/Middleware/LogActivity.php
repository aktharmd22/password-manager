<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level middleware that records a generic "viewed" audit entry. Apply
 * selectively (e.g. on credentials.show) — applying it globally would flood
 * the audit log with noise like dashboard pageviews.
 *
 * For credential-specific events with extra metadata, controllers should
 * call AuditService directly rather than rely on this middleware.
 */
class LogActivity
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log successful GET requests so failed/redirected ones don't dirty the audit.
        if ($request->isMethod('GET') && $response->isSuccessful() && $request->user()) {
            $this->audit->log(
                'viewed',
                $request->user(),
                metadata: ['route' => $request->route()?->getName(), 'path' => $request->path()],
                request: $request,
            );
        }

        return $response;
    }
}
