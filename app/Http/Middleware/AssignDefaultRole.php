<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenceService;
use Spatie\Permission\Models\Role;

/**
 * Middleware to assign default roles to users based on authentication status
 */
class AssignDefaultRole
{
    protected LicenceService $licenceService;

    public function __construct(LicenceService $licenceService)
    {
        $this->licenceService = $licenceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Check if roles exist before trying to assign them (for testing environments)
            if (!$this->rolesExist()) {
                return $next($request);
            }

            // Assign 'user' role if the user has no roles (except guest)
            if (!$user->roles()->whereNotIn('name', ['guest'])->exists()) {
                if ($user->hasRole('guest')) {
                    $user->removeRole('guest');
                }
                $user->assignRole('user');
            }

            // Check and update adherent role based on licence validity
            $this->licenceService->checkAndAssignAdherentRole($user);

        } else {
            // For non-authenticated users, we don't assign roles
            // Guest permissions are handled by default in policies
        }

        return $next($request);
    }

    /**
     * Check if the required roles exist in the database
     *
     * @return bool
     */
    protected function rolesExist(): bool
    {
        try {
            return Role::where('name', 'user')->where('guard_name', 'web')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }
}
