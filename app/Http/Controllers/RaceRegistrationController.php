<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Services\LicenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controller for managing race registrations
 */
class RaceRegistrationController extends Controller
{
    protected LicenceService $licenceService;

    public function __construct(LicenceService $licenceService)
    {
        $this->licenceService = $licenceService;
    }

    /**
     * Check if user can register for a race
     *
     * @param Race $race
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEligibility(Race $race)
    {
        $user = auth()->user();

        // Check if user can register
        if (!Gate::allows('register', $race)) {
            return response()->json([
                'can_register' => false,
                'reason' => 'permission_denied',
                'message' => __('messages.no_permission_to_register'),
            ]);
        }

        // Check if user has valid licence or PPS
        $hasValidCredentials = $this->licenceService->hasValidCredentials($user);

        return response()->json([
            'can_register' => $hasValidCredentials,
            'has_valid_licence' => $this->licenceService->hasValidLicence($user),
            'has_valid_pps' => $this->licenceService->hasValidPps($user),
            'needs_credentials' => !$hasValidCredentials,
            'licence_expiry_date' => $user->licence_expiry_date,
            'pps_expiry_date' => $user->pps_expiry_date,
        ]);
    }

    /**
     * Register a user for a race
     *
     * @param Request $request
     * @param Race $race
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request, Race $race)
    {
        $user = auth()->user();

        // Check permission
        if (!Gate::allows('register', $race)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.no_permission_to_register'),
            ], 403);
        }

        // Check if user has valid credentials
        if (!$this->licenceService->hasValidCredentials($user)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.need_valid_credentials'),
                'needs_credentials' => true,
            ], 400);
        }

        try {
            // TODO: Implement actual race registration logic
            // This would involve creating a participant record, etc.

            return response()->json([
                'success' => true,
                'message' => __('messages.registration_successful'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
