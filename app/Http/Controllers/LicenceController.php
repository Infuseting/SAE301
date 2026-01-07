<?php

namespace App\Http\Controllers;

use App\Services\LicenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for managing user licences and PPS codes
 */
class LicenceController extends Controller
{
    protected LicenceService $licenceService;

    public function __construct(LicenceService $licenceService)
    {
        $this->licenceService = $licenceService;
    }

    /**
     * Store a new licence for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeLicence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'licence_number' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $this->licenceService->addLicence($user, $request->licence_number);

            return response()->json([
                'success' => true,
                'message' => __('messages.licence_added_successfully'),
                'licence_info' => $this->licenceService->getLicenceInfo($user->fresh()),
                'roles' => $user->fresh()->getRoleNames(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new PPS code for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storePpsCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pps_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $this->licenceService->addPpsCode($user, $request->pps_code);

            return response()->json([
                'success' => true,
                'message' => __('messages.pps_added_successfully'),
                'licence_info' => $this->licenceService->getLicenceInfo($user->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the authenticated user has valid credentials
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCredentials()
    {
        $user = auth()->user();

        return response()->json($this->licenceService->getLicenceInfo($user));
    }
}
