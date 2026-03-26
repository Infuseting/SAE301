<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\ApiResponseTrait;
use App\Services\LicenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that blocks managers without valid license from accessing protected routes
 * This prevents bypassing the frontend modal by directly accessing URLs
 */
class EnsureManagerHasValidLicence
{
    use ApiResponseTrait;
    protected LicenceService $licenceService;

    public function __construct(LicenceService $licenceService)
    {
        $this->licenceService = $licenceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admin bypass - admins don't need a license
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Check if user is a manager
        $isManager = $user->hasAnyRole([
            'responsable-club',
            'gestionnaire-raid',
            'responsable-course',
            'gestionnaire-equipe'
        ]);

        if (!$isManager) {
            return $next($request);
        }

        // Load member relationship if not loaded
        if (!$user->relationLoaded('member')) {
            $user->load('member');
        }

        // Check if manager has valid license
        $hasValidLicence = $this->licenceService->hasValidLicence($user);

        if ($hasValidLicence) {
            return $next($request);
        }

        // Allow only specific routes for managers without license
        $allowedRoutes = [
            'profile.update',
            'profile.edit',
            'profile.index',
            'profile.show',
            'logout',
            'licence.store',
            'pps.store',
            'credentials.check',
        ];

        $currentRoute = $request->route()?->getName();

        if (in_array($currentRoute, $allowedRoutes, true)) {
            return $next($request);
        }

        // For GET requests, let the page load so the modal can be displayed
        // The modal will block interaction on the frontend
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Block POST/PUT/DELETE actions with appropriate response
        if ($request->expectsJson() || $request->header('X-Inertia')) {

            return $this->forbiddenResponse('Votre licence est invalide ou expirée. Veuillez mettre à jour votre licence pour continuer.', [
                'requires_licence_update' => true
            ]);
        }

        // For regular POST requests (non-Inertia), redirect to profile edit
        return redirect()->route('profile.edit')
            ->with('error', 'Votre licence est invalide ou expirée. Veuillez la mettre à jour pour accéder à cette fonctionnalité.');
    }
}
