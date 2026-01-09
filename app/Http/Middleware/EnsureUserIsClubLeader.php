<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClubLeader
{
    /**
     * Handle an incoming request.
     * Allows admin and gestionnaire-raid users to bypass the club leader requirement.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        //

        // Allow admins to bypass the club leader requirement
        if ($user && $user->hasRole('admin')) {
            return $next($request);
        }

        // Allow gestionnaire-raid to manage raids they are responsible for
        if ($user && $user->hasRole('gestionnaire-raid')) {
            return $next($request);
        }
        if (!$user || !$user->isClubLeader()) {
            abort(403, 'Only club leaders can perform this action.');
        }

        return $next($request);
    }
}
