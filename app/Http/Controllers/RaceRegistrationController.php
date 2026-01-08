<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Services\LicenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Annotations as OA;

/**
 * Controller for managing race registrations
 * 
 * @OA\Tag(
 *     name="Race Registration",
 *     description="Endpoints for race registration and eligibility checks"
 * )
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
     * @OA\Get(
     *     path="/races/{race}/registration/check",
     *     tags={"Race Registration"},
     *     summary="Check registration eligibility",
     *     description="Checks if the authenticated user is eligible to register for a specific race",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         description="Race ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Eligibility check result",
     *         @OA\JsonContent(
     *             @OA\Property(property="can_register", type="boolean", example=true),
     *             @OA\Property(property="has_valid_licence", type="boolean", example=true),
     *             @OA\Property(property="has_valid_pps", type="boolean", example=false),
     *             @OA\Property(property="needs_credentials", type="boolean", example=false),
     *             @OA\Property(property="licence_expiry_date", type="string", format="date", example="2026-12-31"),
     *             @OA\Property(property="pps_expiry_date", type="string", format="date", example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Race not found")
     * )
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
     * @OA\Post(
     *     path="/races/{race}/register",
     *     tags={"Race Registration"},
     *     summary="Register for a race",
     *     description="Registers the authenticated user for a specific race",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         description="Race ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Validation failed or credentials missing",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Valid licence or PPS code required"),
     *             @OA\Property(property="needs_credentials", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Permission denied"),
     *     @OA\Response(response=404, description="Race not found")
     * )
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
