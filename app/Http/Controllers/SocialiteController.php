<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

use OpenApi\Annotations as OA;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     *
     * @OA\Get(
     *     path="/auth/{provider}/redirect",
     *     tags={"Auth"},
     *     summary="Social Login Redirect",
     *     description="Redirects the user to the OAuth provider (google, github, discord)",
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="The social provider",
     *         @OA\Schema(type="string", enum={"google"})
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to provider"
     *     )
     * )
     */
    public function redirect(Request $request, $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the provider.
     *
     * @OA\Get(
     *     path="/auth/{provider}/callback",
     *     tags={"Auth"},
     *     summary="Social Login Callback",
     *     description="Handle the callback from the OAuth provider",
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="The social provider",
     *         @OA\Schema(type="string", enum={"google"})
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to dashboard or login"
     *     )
     * )
     */
    public function callback(Request $request, $provider)
    {
        try {
            /** @var \Laravel\Socialite\Two\User $socialUser */
            $socialUser = Socialite::driver($provider)
                ->setHttpClient(new \GuzzleHttp\Client(['verify' => false]))
                ->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Unable to login with ' . $provider . ': ' . $e->getMessage()]);
        }

        // If user is already logged in, link the account
        if (Auth::check()) {
            $user = Auth::user();

            // Check if already linked
            $existing = $user->connectedAccounts()
                ->where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if (!$existing) {
                $user->connectedAccounts()->create([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'token' => $socialUser->token,
                    'secret' => $socialUser->tokenSecret ?? null,
                    'refresh_token' => $socialUser->refreshToken ?? null,
                    'expires_at' => property_exists($socialUser, 'expiresIn') ? now()->addSeconds($socialUser->expiresIn) : null,
                ]);
            }

            return redirect()->route('profile.edit')->with('status', 'Account linked successfully!');
        }

        // Guest: Login or Register
        $account = ConnectedAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        // Handle orphaned account (account exists but user does not)
        if ($account && !$account->user) {
            $account->delete();
            $account = null;
        }

        if ($account) {
            Auth::login($account->user, true);
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        // Check if user with existing email exists
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Create new user (Generate random password)
            // Create new user (Generate random password)
            $nameParts = explode(' ', $socialUser->getName() ?? $socialUser->getNickname() ?? 'User');
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : $firstName;

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
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $socialUser->getEmail(),
                'password' => bcrypt(str()->random(32)),
                'password_is_set' => false,
                'email_verified_at' => now(), // Assume verified by provider
                'adh_id' => $member->adh_id,
                'doc_id' => $medicalDoc->doc_id,
            ]);
        }

        // Link account
        $user->connectedAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'token' => $socialUser->token,
            'secret' => $socialUser->tokenSecret ?? null,
            'refresh_token' => $socialUser->refreshToken ?? null,
            'expires_at' => property_exists($socialUser, 'expiresIn') ? now()->addSeconds($socialUser->expiresIn) : null,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/');
    }
}
