<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPrometheusToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('monitor.prometheus.enabled')) {
            abort(404);
        }

        $token = config('monitor.prometheus.token');

        // If no token is configured, allow access
        if (empty($token)) {
            return $next($request);
        }

        // Check for Bearer token
        $providedToken = $request->bearerToken();

        // Also check query parameter as fallback
        if (!$providedToken) {
            $providedToken = $request->query('token');
        }

        if ($providedToken !== $token) {
            abort(403, 'Invalid Prometheus token');
        }

        return $next($request);
    }
}
