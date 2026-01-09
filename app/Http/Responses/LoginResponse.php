<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Custom login response to redirect to pending invitation after authentication.
 */
class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $token = session()->pull('pending_invitation_token');
        Log::info('LoginResponse called', ['token' => $token, 'session_id' => session()->getId()]);
        
        // Check for pending invitation token in session
        if ($token) {
            Log::info('Redirecting to invitation', ['token' => $token]);
            return Inertia::location(route('invitations.accept', $token));
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(config('fortify.home'));
    }
}

<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $redirectUri = $request->input('redirect_uri');

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
            return redirect()->to($redirectUri);
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
