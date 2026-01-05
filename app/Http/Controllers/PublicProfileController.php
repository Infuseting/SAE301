<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicProfileController extends Controller
{
    /**
     * Display the specified user's public profile.
     *
     * @param  \App\Models\User  $user
     * @return \Inertia\Response
     */
    public function show(User $user)
    {
        if (!$user->is_public) {
            // Option 1: 404
            // Option 2: Show "Private Profile" page
            // We'll show a limited view with "Private Profile" message
            return Inertia::render('Profile/Show', [
                'user' => [
                    'name' => $user->name,
                    'profile_photo_url' => $user->profile_photo_url,
                    'is_public' => false,
                ],
            ]);
        }

        return Inertia::render('Profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'description' => $user->description,
                'profile_photo_url' => $user->profile_photo_url,
                'is_public' => true,
                // Only share safe fields
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Display the authenticated user's profile.
     */
    public function myProfile(Request $request)
    {
        return $this->show($request->user());
    }
}
