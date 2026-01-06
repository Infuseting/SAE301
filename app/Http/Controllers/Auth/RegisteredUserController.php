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
    public function create(): Response
    {
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
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

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
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'adh_id' => $member->adh_id,
            'doc_id' => $medicalDoc->doc_id,
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
        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home', absolute: false));
    }
}
