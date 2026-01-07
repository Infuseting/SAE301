<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Team;

class TeamController extends Controller
{
    /**
     * Show the form for creating a new team.
     */
    public function create()
    {
        return Inertia::render('teams/CreateTeam');
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'leader_id' => 'required|exists:members,adh_id',
            'teammates' => 'nullable|array',
            'join_team' => 'nullable|boolean',
        ]);

        // Create the team
        $team = Team::create([
            'equ_name' => $validated['name'],
            'equ_image' => $request->file('image') ? $request->file('image')->store('teams', 'public') : null,
            'adh_id' => $validated['leader_id'],
        ]);

        return redirect()->route('home')->with('success', 'Team created successfully!');
    }
}
