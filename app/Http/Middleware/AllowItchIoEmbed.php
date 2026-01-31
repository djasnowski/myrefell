<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowItchIoEmbed
{
    /**
     * Handle an incoming request.
     *
     * Allow embedding in iframes from itch.io domains.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove restrictive X-Frame-Options
        $response->headers->remove('X-Frame-Options');

        // Allow embedding from self and itch.io
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self' https://itch.io https://*.itch.io"
        );

        return $response;
    }
}
