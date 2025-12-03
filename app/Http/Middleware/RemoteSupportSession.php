<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoteSupportSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a remote support session
        if (session()->has('remote_support_active') && session()->has('admin_user_id')) {
            // Add a visual indicator or notification to the response
            $request->attributes->set('remote_support_active', true);
        }

        return $next($request);
    }
}
