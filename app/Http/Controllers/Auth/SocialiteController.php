<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
     *     description="Redirects the user to the OAuth provider (google, strava)",
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
        if ($request->has('redirect_uri')) {
            $redirectUri = $request->get('redirect_uri');
            // Ensure UTF-8 validity
            if (mb_check_encoding($redirectUri, 'UTF-8')) {
                $request->session()->put('redirect_uri', $redirectUri);
            }
        }
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
            // Log the raw error for debugging
            \Illuminate\Support\Facades\Log::error("Socialite Login Error ($provider): " . $e->getMessage());
            
            // Return a safe, generic error message to the user to avoid UTF-8 encoding issues in the session/view
            return redirect()->route('login')->withErrors(['email' => "Unable to login with $provider. Please try again."]);
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
            
            $redirectUri = $request->session()->pull('redirect_uri');
            if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
                return redirect()->to($redirectUri);
            }

            return redirect()->intended('/');
        }

        // Check if user with existing email exists
        $email = $socialUser->getEmail();

        // For providers like Strava that don't always return email, generate one
        if (empty($email)) {
            $email = $provider . '_' . $socialUser->getId() . '@' . config('app.name', 'sae301') . '.local';
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create new user (Generate random password)
            $nameParts = explode(' ', $socialUser->getName() ?? $socialUser->getNickname() ?? 'User');
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : $firstName;

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => bcrypt(str()->random(32)),
                'password_is_set' => false,
                'email_verified_at' => now(), // Assume verified by provider
                'adh_id' => null,
                'doc_id' => null,
            ]);

            // Assign default 'user' role
            $user->assignRole('user');
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

        $redirectUri = $request->session()->pull('redirect_uri');
        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
            return redirect()->to($redirectUri);
        }

        return redirect('/');
    }
}
