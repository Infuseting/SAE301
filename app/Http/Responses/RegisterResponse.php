<?php

namespace App\Http\Responses;

<<<<<<< HEAD
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

=======
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * Custom register response to redirect to pending invitation after registration.
 */
>>>>>>> origin/team_invite
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
<<<<<<< HEAD
        $redirectUri = $request->input('redirect_uri');

        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
             return redirect()->to($redirectUri);
        }

        return redirect()->intended(Fortify::redirects('register'));
=======
        // Check for pending invitation token in session
        if ($token = session()->pull('pending_invitation_token')) {
            return Inertia::location(route('invitations.accept', $token));
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(config('fortify.home'));
>>>>>>> origin/team_invite
    }
}
