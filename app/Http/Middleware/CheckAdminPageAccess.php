<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if user has permission to access specific admin pages.
 * 
 * This middleware allows role-based access control for admin pages:
 * - gestionnaire-raid → /admin/raids (permission: access-admin-raids)
 * - responsable-club → /admin/clubs (permission: access-admin-clubs)
 * - responsable-course → /admin/races (permission: access-admin-races)
 * 
 * Permissions are cumulative: users with multiple roles can access multiple pages.
 * Admin users with 'access-admin' permission have full access to all admin pages.
 */
class CheckAdminPageAccess
{
    /**
     * Handle an incoming request.
     * Checks if the user has the required permission to access the admin page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The permission required to access the page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // User must be authenticated
        if (!$user) {
            abort(403, __('auth.unauthenticated'));
        }

        // Admin role has full access to all admin pages
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Check if user has the specific permission for this admin page
        if (!$user->can($permission)) {
            abort(403, __('auth.unauthorized'));
        }

        return $next($request);
    }
}
