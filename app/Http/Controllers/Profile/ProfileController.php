<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileCompletionRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ProfileService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use OpenApi\Annotations as OA;
use App\Models\Member;

class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display the user's profile form.
     *
     * @OA\Get(
     *      path="/profile",
     *      operationId="getProfile",
     *      tags={"Profile"},
     *      summary="Get user profile",
     *      description="Returns user profile data or renders profile edit view",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      )
     * )
     */
    public function edit(Request $request)
    {
        $user = $request->user();

        return $this->respondWith($user, 'Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'connectedAccounts' => $user->connectedAccounts,
            'hasPassword' => $user->password_is_set ?? false, // Safe fallback
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * @OA\Patch(
     *      path="/profile",
     *      operationId="updateProfile",
     *      tags={"Profile"},
     *      summary="Update user profile",
     *      description="Updates user profile data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"name","email"},
     *                  @OA\Property(property="name", type="string", example="John Doe"),
     *                  @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                  @OA\Property(property="description", type="string", example="Runner bio"),
     *                  @OA\Property(property="is_public", type="boolean", example=true),
     *                  @OA\Property(property="license_number", type="string", example="LIC12345"),
     *                  @OA\Property(property="photo", type="string", format="binary", description="Profile photo upload")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile updated",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      )
     * )
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        // Ensure is_public is always set (false if not present, true if checked)
        if (!isset($validated['is_public'])) {
            $validated['is_public'] = false;
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Handle Profile Photo
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($request->user()->profile_photo_path) {
                Storage::disk('public')->delete($request->user()->profile_photo_path);
            }
            $path = $request->file('photo')->store('profile-photos', 'public');
            $request->user()->profile_photo_path = $path;
        }

        // Handle Description and Public Toggle (if passed in request, strictly speaking validted by ProfileUpdateRequest)
        // We might need to update ProfileUpdateRequest to include these rules.
        if ($request->has('description')) {
            $request->user()->description = $request->input('description');
        }

        // Handle License Number (Stored in Members table)
        if (array_key_exists('license_number', $validated)) {
            $licenseNumber = $validated['license_number'];
            unset($validated['license_number']); // Remove from user attributes

            if ($licenseNumber) {
                if ($request->user()->member) {
                    $request->user()->member->update(['adh_license' => $licenseNumber]);
                } else {
                    // Create new member record if not exists
                    $member = Member::create([
                        'adh_license' => $licenseNumber,
                        'adh_end_validity' => now()->addYear(), // Default 1 year validity
                        'adh_date_added' => now(),
                    ]);
                    $request->user()->member()->associate($member);
                }
            } elseif ($request->user()->member) {
                // If license number is cleared/empty, should we clear it in member?
                $request->user()->member->update(['adh_license' => '']);
            }
        }

        $request->user()->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => $request->user()], 200);
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Complete the user's required profile information.
     *
     * @OA\Post(
     *      path="/profile/complete",
     *      operationId="completeProfile",
     *      tags={"Profile"},
     *      summary="Complete user profile",
     *      description="Completes the user profile with required information (DOB, Address, Phone, License/Medical)",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"birth_date", "address", "phone"},
     *              @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *              @OA\Property(property="address", type="string", example="123 Main St, Paris, France"),
     *              @OA\Property(property="phone", type="string", example="+33612345678"),
     *              @OA\Property(property="license_number", type="string", nullable=true, example="123456"),
     *              @OA\Property(property="medical_certificate_code", type="string", nullable=true, example="PPS-123456")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile completed successfully",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      )
     * )
     */
    public function complete(ProfileCompletionRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Handle License Number for Completion
        if (isset($data['license_number'])) {
            $licenseNumber = $data['license_number'];
            unset($data['license_number']);

            if ($licenseNumber) {
                if ($user->member) {
                    $user->member->update(['adh_license' => $licenseNumber]);
                } else {
                    $member = Member::create([
                        'adh_license' => $licenseNumber,
                        'adh_end_validity' => now()->addYear(),
                        'adh_date_added' => now(),
                    ]);
                    $user->member()->associate($member);
                }
            }
        }

        $user->update($data);

        return Redirect::route('dashboard')->with('status', 'profile-completed');
    }

    /**
     * Delete the user's account.
     *
     * @OA\Delete(
     *      path="/profile",
     *      operationId="deleteProfile",
     *      tags={"Profile"},
     *      summary="Delete user account",
     *      description="Deletes the authenticated user account",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"confirmation"},
     *              @OA\Property(property="confirmation", type="string", example="CONFIRMER", description="Must match 'CONFIRMER' exactly")
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Account deleted"
     *      )
     * )
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // All users must type "CONFIRMER" to delete their account
        $request->validate([
            'confirmation' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== 'CONFIRMER') {
                        $fail(__('messages.invalid_confirmation_text'));
                    }
                }
            ],
        ], [
            'confirmation.required' => __('messages.confirmation_text_required'),
        ]);

        $this->profileService->deleteAccount($user);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => __('messages.account_deleted')], 204);
        }

        return Redirect::to('/');
    }
}
