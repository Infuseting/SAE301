<?php

namespace App\Http\Controllers;


use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ProfileService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
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
    public function update(ProfileUpdateRequest $request)
    {
        $user = $this->profileService->update($request->user(), $request->validated());

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => $user,
            ]);
        }

        return Redirect::route('profile.edit');
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
            return response()->json(['message' => 'Account deleted'], 204);
        }

        return Redirect::to('/');
    }
}
