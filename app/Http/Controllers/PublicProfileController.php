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
    public function show(Request $request, User $user)
    {
        $isOwner = $request->user() && $request->user()->id === $user->id;

        if (!$user->is_public && !$isOwner) {
            // Option 1: 404
            // Option 2: Show "Private Profile" page
            // We'll show a limited view with "Private Profile" message
            return Inertia::render('Profile/Show', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile_photo_url' => $user->profile_photo_url,
                    'is_public' => false,
                ],
                'isOwner' => false,
            ]);
        }

        return Inertia::render('Profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'description' => $user->description,
                'profile_photo_url' => $user->profile_photo_url,
                'is_public' => $user->is_public,
                'license_number' => $user->license_number,
                'medical_certificate_code' => $user->medical_certificate_code,
                'birth_date' => $user->birth_date,
                'address' => $user->address,
                'city' => $user->city,
                'phone' => $user->phone,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'licence_end_validity' => $user->licence_end_validity,
            ],
            'teams' => $user->teams()->get()->map(function ($team) {
                return [
                    'id' => $team->equ_id,
                    'name' => $team->equ_name,
                    'image' => $team->equ_image,
                    'members' => $team->users()->get()->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                    ])->toArray(),
                ];
            })->toArray(),
            'races' => $user->races()->get()->map(function ($race) {
                return [
                    'id' => $race->race_id, 
                    'name' => $race->race_name, 
                    'date' => $race->race_date, 
                ];
            })->toArray(),
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Display the authenticated user's profile.
     */
    public function myProfile(Request $request)
    {
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json($request->user());
        }

        return $this->show($request, $request->user());
    }
}
