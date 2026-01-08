<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * Custom register response to redirect to pending invitation after registration.
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
        // Check for pending invitation token in session
        if ($token = session()->pull('pending_invitation_token')) {
            return Inertia::location(route('invitations.accept', $token));
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(config('fortify.home'));
    }
}
