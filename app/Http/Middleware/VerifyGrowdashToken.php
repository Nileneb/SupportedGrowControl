<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGrowdashToken
{
    /**
     * Handle an incoming request from Growdash devices.
     *
     * Validates that the request contains a valid X-Growdash-Token header
     * matching the configured webhook token.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Growdash-Token');
        $expected = config('services.growdash.webhook_token');

        if (!$expected || $token !== $expected) {
            abort(403, 'Invalid Growdash token');
        }

        return $next($request);
    }
}
