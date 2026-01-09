<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        // Store redirect_uri in session if provided
        if ($request->has('redirect_uri')) {
            $redirectUri = $request->get('redirect_uri');
            if (mb_check_encoding($redirectUri, 'UTF-8')) {
                $request->session()->put('redirect_uri', $redirectUri);
            }
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        // Get pending invitation token before session regeneration
        $pendingToken = session('pending_invitation_token');
        
        $request->authenticate();

        $request->session()->regenerate();

        // Check for redirect_uri in session
        $redirectUri = $request->session()->pull('redirect_uri');
        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
            return redirect()->to($redirectUri);
        }
        
        // Check for pending invitation and redirect to it
        if ($pendingToken) {
            return Inertia::location(route('invitations.show', $pendingToken));
        }

        return redirect()->intended('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
