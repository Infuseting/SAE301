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
     * and adds the creator based on their selected role (leader or teammate).
     * Prevents duplicate entries and ensures the creator is not added multiple times.
     */
    public function store(Request $request)
    {
        // Le créateur est toujours le leader
        $creatorId = $request->user()->id;

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'teammates' => 'nullable|array',
            'teammates.*.id' => 'integer|exists:users,id',
            'join_team' => 'nullable|boolean',
        ]);

        // Vérifier qu'il y a au moins un participant (créateur ou coéquipiers)
        $joinTeam = $validated['join_team'] ?? false;
        $hasTeammates = !empty($validated['teammates']);
        
        if (!$joinTeam && !$hasTeammates) {
            return back()->withErrors([
                'teammates' => 'L\'équipe doit avoir au moins un participant. Cochez "Je participe" ou ajoutez des coéquipiers.'
            ])->withInput();
        }

        // Create the team with the creator as leader
        $team = Team::create([
            'equ_name' => $validated['name'],
            'equ_image' => $request->file('image') ? $request->file('image')->store('teams', 'public') : null,
            'user_id' => $creatorId,
        ]);
        
        $usersToAttach = [];

        if (!empty($validated['join_team']) && $validated['join_team']) {
            // If the creator wants to join as a teammate, add them
            $usersToAttach[] = $creatorId;
        }

        // Add teammates to the team
        if (!empty($validated['teammates'])) {
            $teammateIds = array_map(fn($teammate) => (int) $teammate['id'], $validated['teammates']);
            foreach ($teammateIds as $id) {
                if ($id !== $creatorId) {
                    $usersToAttach[] = $id;
                }
            }
        }        
        if (!empty($usersToAttach)) {
            $team->users()->attach(array_unique($usersToAttach));
        }
        
        return redirect()->route('dashboard')->with('success', 'Équipe créée avec succès!');
    }
}
