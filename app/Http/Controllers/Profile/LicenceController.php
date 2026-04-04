<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\LicenceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\ApiResponseTrait;

/**
 * Controller for managing user licenses and PPS codes
 */
class LicenceController extends Controller
{
    use ApiResponseTrait;

    protected LicenceService $licenceService;

    public function __construct(LicenceService $licenceService)
    {
        $this->licenceService = $licenceService;
    }

    /**
     * Store a new license for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse|null
     */
    public function storeLicence(Request $request): ?JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'licence_number' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->unprocessableContentResponse($validator->errors()->all());
        }

        try {
            $user = auth()->user();
            $this->licenceService->addLicence($user, $request->licence_number);

            return $this->successResponse([
                'message' => __('messages.licence_added_successfully'),
                'licence_info' => $this->licenceService->getLicenceInfo($user->fresh()),
                'roles' => $user->fresh()->getRoleNames(),
            ]);
        } catch (Exception $e) {
            return $this->unprocessableContentResponse($e->getMessage());
        }
    }

    /**
     * Store a new PPS code for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse|null
     */
    public function storePpsCode(Request $request): ?JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pps_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->unprocessableContentResponse($validator->errors()->all());
        }

        try {
            $user = auth()->user();
            $this->licenceService->addPpsCode($user, $request->pps_code);

            return $this->successResponse([
                'message' => __('messages.pps_added_successfully'),
                'licence_info' => $this->licenceService->getLicenceInfo($user->fresh()),
            ]);
        } catch (Exception $e) {
            return $this->unprocessableContentResponse($e->getMessage());
        }
    }

    /**
     * Check if the authenticated user has valid credentials
     *
     * @return JsonResponse
     */
    public function checkCredentials(): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse([
            $this->licenceService->getLicenceInfo($user)
        ]);
    }
}
