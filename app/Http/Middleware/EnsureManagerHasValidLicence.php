<?php

namespace App\Http\Middleware;

use App\Services\LicenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that blocks managers without valid licence from accessing protected routes
 * This prevents bypassing the frontend modal by directly accessing URLs
 */
class EnsureManagerHasValidLicence
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
        $user = $request->user();

        if (!$user) {
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

        // Check if manager has valid licence
        $hasValidLicence = $this->licenceService->hasValidLicence($user);

        if ($hasValidLicence) {
            return $next($request);
        }

        // Allow only specific routes for managers without licence
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

        if (in_array($currentRoute, $allowedRoutes)) {
            return $next($request);
        }

        // For GET requests, let the page load so the modal can be displayed
        // The modal will block interaction on the frontend
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Block POST/PUT/DELETE actions with appropriate response
        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Votre licence est invalide ou expirée. Veuillez mettre à jour votre licence pour continuer.',
                'requires_licence_update' => true,
            ], 403);
        }

        // For regular POST requests (non-Inertia), redirect to profile edit
        return redirect()->route('profile.edit')
            ->with('error', 'Votre licence est invalide ou expirée. Veuillez la mettre à jour pour accéder à cette fonctionnalité.');
    }
}
