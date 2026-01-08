<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
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

        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'adh_id' => null,
            'doc_id' => null,
        ]);

        // Log user creation
        activity()
            ->causedBy($user)
            ->withProperties([
                'level' => 'info',
                'action' => 'USER_CREATED',
                'content' => ['name' => $user->name, 'email' => $user->email],
                'ip' => $request->ip(),
            ])
            ->log('USER_CREATED');

        // Assign default 'user' role
        $user->assignRole('user');

        event(new Registered($user));

        Auth::login($user);

        // Check for redirect_uri in session
        $redirectUri = $request->session()->pull('redirect_uri');
        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL) && str_starts_with($redirectUri, url('/'))) {
            return redirect()->to($redirectUri);
        }

        return redirect(route('home', absolute: false));
    }
}
