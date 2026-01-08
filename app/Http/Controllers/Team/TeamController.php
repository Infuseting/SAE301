<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;
use App\Models\Invitation;
use App\Mail\TeamInvitation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    /**
     * Display team details.
     */
    public function show(Team $team)
    {
        // Get team members
        $members = $team->users()->get()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ])->toArray();

        // Get all users for invitation
        $users = User::all()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ])->toArray();

        return Inertia::render('Team/Show', [
            'team' => [
                'id' => $team->equ_id,
                'name' => $team->equ_name,
                'image' => $team->equ_image ? '/storage/' . $team->equ_image : null,
                'members' => $members,
                'created_at' => $team->created_at->format('d/m/Y'),
                'creator_id' => $team->user_id,  
            ],
            'users' => $users,
        ]);
    }

    /**
     * Send invitation email to a new user.
     */
    public function inviteByEmail(Team $team, Request $request)
    {
        // Verify the user is the team creator
        if ($request->user()->id !== $team->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate email
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Create invitation record
        $token = Str::random(64);
        $invitation = Invitation::create([
            'equ_id' => $team->equ_id,
            'email' => $validated['email'],
            'inviter_id' => $request->user()->id,
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Send invitation email
        Mail::to($validated['email'])->send(new TeamInvitation($team->equ_name, $request->user()->name));

        return response()->json(['success' => 'Email d\'invitation envoyé avec succès']);
    }
}
