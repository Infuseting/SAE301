<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Member;

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
     * Store a newly created team with members.
     * 
     * Creates a new team, adds the leader, teammates, and optionally
     * the creator if the join_team checkbox was selected.
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

        // Create the team
        $team = Team::create([
            'equ_name' => $validated['name'],
            'equ_image' => $request->file('image') ? $request->file('image')->store('teams', 'public') : null,
            'adh_id' => $this->getUserMemberId($validated['leader_id']),
        ]);

        // Add leader to has_participate
        $leaderMemberId = $this->getUserMemberId($validated['leader_id']);
        if ($leaderMemberId) {
            $team->members()->attach($leaderMemberId);
        }

        // Add teammates to has_participate
        if (!empty($validated['teammates'])) {
            foreach ($validated['teammates'] as $teammate) {
                $teammateMemberId = $this->getUserMemberId($teammate['id']);
                if ($teammateMemberId) {
                    $team->members()->attach($teammateMemberId);
                }
            }
        }

        // Add creator to team if checkbox is checked
        if ($validated['join_team'] ?? false) {
            $creatorMemberId = $this->getUserMemberId($request->user()->id);
            if ($creatorMemberId) {
                $team->members()->attach($creatorMemberId);
            }
        }

        return redirect()->route('dashboard')->with('success', 'Équipe créée avec succès!');
    }

    /**
     * Get the member ID (adh_id) for a given user ID.
     * 
     * @param integer $userId
     * @return integer|null
     */
    private function getUserMemberId($userId)
    {
        $user = \App\Models\User::find($userId);
        return $user?->adh_id;
    }
}
