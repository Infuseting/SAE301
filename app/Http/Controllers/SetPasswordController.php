<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SetPasswordController extends Controller
{
    /**
     * Set the user's password.
     */
    public function store(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->password),
            'password_is_set' => true,
        ])->save();

        return back()->with('status', 'password-updated');
    }
}
