<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class AuthenticateUserOrAgent
{
    /**
     * Handle an incoming request.
     *
     * Authenticates using EITHER user session (auth:web) OR API token (auth:sanctum).
     * This allows both browser-based users and API clients to access the same endpoints.
     * 
     * IMPORTANT: This middleware REQUIRES StartSession to be loaded BEFORE it runs!
     * Session must be available in the request when this middleware executes.
     */
    public function handle(Request $request, Closure $next)
    {
        // Session wurde durch bootstrap/app.php StartSession Middleware bereits initialisiert
        
        // Try web guard (session) - nur wenn session_id vorhanden
        if ($request->session() && $request->session()->getId()) {
            if (auth('web')->check()) {
                return $next($request);
            }
        }

        // Try sanctum guard (API token)
        if (auth('sanctum')->check()) {
            return $next($request);
        }

        // Neither auth method worked
        throw new AuthenticationException('Unauthenticated.');
    }
}

