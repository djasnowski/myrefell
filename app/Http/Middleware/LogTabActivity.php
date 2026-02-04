<?php

namespace App\Http\Middleware;

use App\Models\TabActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogTabActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only log POST/PUT/DELETE requests (state-changing actions)
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Only log for authenticated users
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Get tab ID from header
        $tabId = $request->header('X-Tab-ID');
        if (! $tabId) {
            return $next($request);
        }

        // Validate UUID format
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tabId)) {
            return $next($request);
        }

        // Log the activity asynchronously (don't block the request)
        try {
            TabActivityLog::logActivity(
                userId: $user->id,
                tabId: $tabId,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                route: $request->path(),
                method: $request->method()
            );
        } catch (\Exception $e) {
            // Don't fail the request if logging fails
            report($e);
        }

        return $next($request);
    }
}
