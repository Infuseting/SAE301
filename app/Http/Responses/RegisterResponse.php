<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

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
        $redirectUri = $request->input('redirect_uri');

        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
             return redirect()->to($redirectUri);
        }

        return redirect()->intended(Fortify::redirects('register'));
    }
}
