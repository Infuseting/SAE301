<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

/**
 * Custom register response to handle pending invitations and custom redirects.
 */
class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        // Handle JSON responses
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        // Priority 1: Check for pending invitation token in session
        $token = session()->pull('pending_invitation_token');
        if ($token) {
            return Inertia::location(route('invitations.accept', $token));
        }

        // Priority 2: Check for custom redirect_uri (with security validation)
        $redirectUri = $request->input('redirect_uri');
        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
            return redirect()->to($redirectUri);
        }

        // Priority 3: Default redirection
        return redirect()->intended(Fortify::redirects('register'));
    }
}
