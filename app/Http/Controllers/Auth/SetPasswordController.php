<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;

class SetPasswordController extends Controller
{
    /**
     * Set the user's password.
     *
     * @OA\Put(
     *     path="/user/set-password",
     *     tags={"Profile"},
     *     summary="Set user password",
     *     description="Sets the password for the authenticated user (typically for social login users)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password", "password_confirmation"},
     *             @OA\Property(property="password", type="string", format="password", example="new-password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="new-password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request, ProfileService $profileService)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $profileService->setPassword($request->user(), $request->password);

        return $this->respondWith(['message' => __('messages.password_updated')], 'Profile/Edit', [
            'status' => 'password-updated'
        ]);
    }
}
