<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    /**
     * Handle an incoming login request.
     *
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login user",
     *     description="Login and retrieve an API token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="token", type="string", example="1|AbCdEf123456...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => __('messages.invalid_credentials')
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => __('messages.login_successful'),
            'token' => $token,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register user",
     *     description="Register a new user and retrieve an API token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="token", type="string", example="1|AbCdEf123456..."),
     *              @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $member = \App\Models\Member::create([
            'adh_license' => 'PENDING-' . \Illuminate\Support\Str::random(8),
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        $medicalDoc = \App\Models\MedicalDoc::create([
            'doc_num_pps' => 'PENDING',
            'doc_end_validity' => now()->addYear(),
            'doc_date_added' => now(),
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'adh_id' => $member->adh_id,
            'doc_id' => $medicalDoc->doc_id,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => __('messages.user_registered'),
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}
