<?php

namespace App\Http\Controllers;


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
     *          @OA\JsonContent(
     *              required={"name","email"},
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile updated",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      )
     * )
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

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
        if ($request->has('is_public')) {
            $request->user()->is_public = $request->boolean('is_public');
        }

        $request->user()->save();

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

        // Handle the mapping from input 'medical_certificate' to DB column 'medical_certificate_code'
        if (isset($data['medical_certificate'])) {
            $data['medical_certificate_code'] = $data['medical_certificate'];
            unset($data['medical_certificate']);
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
     *              required={"password"},
     *              @OA\Property(property="password", type="string", format="password", example="secret")
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
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $this->profileService->deleteAccount($request->user());

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => __('messages.account_deleted')], 204);
        }

        return Redirect::to('/');
    }
}
