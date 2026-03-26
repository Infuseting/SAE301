<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom login response to handle pending invitations and custom redirects.
 */
class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request): Response
    {
        // Handle JSON responses
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        // Priority 1: Check for pending invitation token in session
        $token = session()->pull('pending_invitation_token');
        if ($token) {
            Log::info('Redirecting to invitation after login', ['token' => $token]);
            return Inertia::location(route('invitations.accept', $token));
        }

        // Priority 2: Check for custom redirect_uri (with security validation)
        $redirectUri = $request->input('redirect_uri');
        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
            return redirect()->to($redirectUri);
        }

        // Priority 3: Default redirection
        return redirect()->intended(Fortify::redirects('login'));
    }
}
