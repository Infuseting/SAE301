<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Attempt to authenticate the request using a Sanctum personal access token
 * if present, but do not require authentication. This allows public routes
 * to still recognize Bearer tokens when provided by API clients.
 */
class AttemptSanctumAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // If already authenticated, nothing to do
        if (auth()->check()) {
            return $next($request);
        }

        // If Authorization: Bearer <token> header is present, try to resolve
        // the Sanctum personal access token and set the authenticated user.
        $token = $request->bearerToken();
        if ($token) {
            try {
                $pat = PersonalAccessToken::findToken($token);
                if ($pat && $pat->tokenable) {
                    auth()->setUser($pat->tokenable);
                }
            } catch (\Throwable $e) {
                // Do not interrupt the request on errors — treat as unauthenticated
            }
        }

        return $next($request);
    }
}
