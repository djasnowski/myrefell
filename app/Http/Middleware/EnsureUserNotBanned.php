<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    /**
     * Routes that banned users are allowed to access.
     */
    private const ALLOWED_ROUTES = [
        'banned',
        'banned.appeal',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            $currentRoute = $request->route()?->getName();

            // Allow access to ban page, appeal submission, and logout
            if (in_array($currentRoute, self::ALLOWED_ROUTES)) {
                return $next($request);
            }

            return redirect()->route('banned');
        }

        return $next($request);
    }
}
