<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;

class TeamController extends Controller
{
    /**
     * Show the form for creating a new team.
     */
    public function create()
    {
        return Inertia::render('Team/CreateTeam');
    }

    /**
     * Store a newly created team with users.
     * 
     * Creates a new team, assigns the leader and teammates,
     * and optionally adds the creator if the join_team checkbox was selected.
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'leader_id' => 'required|exists:users,id',
            'teammates' => 'nullable|array',
            'teammates.*.id' => 'integer|exists:users,id',
            'join_team' => 'nullable|boolean',
        ]);

        // Create the team with the leader's user ID
        $team = Team::create([
            'equ_name' => $validated['name'],
            'equ_image' => $request->file('image') ? $request->file('image')->store('teams', 'public') : null,
            'adh_id' => $validated['leader_id'],
        ]);

        // Add leader to the team
        $team->users()->attach($validated['leader_id']);

        // Add teammates to the team
        if (!empty($validated['teammates'])) {
            $teammatIds = array_map(fn($teammate) => $teammate['id'], $validated['teammates']);
            $team->users()->attach($teammatIds);
        }

        // Add creator to team if checkbox is checked
        if ($validated['join_team'] ?? false) {
            $team->users()->attach($request->user()->id);
        }

        return redirect()->route('dashboard')->with('success', 'Équipe créée avec succès!');
    }
}
