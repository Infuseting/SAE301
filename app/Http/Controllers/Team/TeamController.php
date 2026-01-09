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
            'name' => 'required|string|max:32',
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
     * Send invitation email to a user.
     */
    public function inviteByEmail(Team $team, Request $request, User $user = null)
    {
        if ($request->user()->id !== $team->user_id) return response()->json(['error' => 'Unauthorized'], 403);
        
        $email = $user?->email ?? $request->validate(['email' => 'required|email'])['email'];
        
        Invitation::create([
            'inviter_id' => $request->user()->id,
            'invitee_id' => $user?->id,
            'equ_id' => $team->equ_id,
            'email' => $email,
            'token' => Str::random(64),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
        
        Mail::to($email)->send(new TeamInvitation($team->equ_name, $request->user()->name));
        return redirect()->back()->with('success', 'Invitation envoyée');
    }

    /**
     * Display registration ticket with QR code
     * Shows the QR code for a validated team registration
     */
    public function showRegistrationTicket(Team $team, int $registrationId)
    {
        $user = auth()->user();
        
        // Check if user is team leader or member
        $isTeamLeader = $team->user_id === $user->id;
        $isTeamMember = $team->users()->where('users.id', $user->id)->exists();
        
        if (!$isTeamLeader && !$isTeamMember) {
            abort(403, 'Unauthorized. You must be a team member to view this ticket.');
        }

        // Get registration with race and raid information
        $registration = \App\Models\Registration::with(['race.raid', 'team.leader', 'team.users'])
            ->where('reg_id', $registrationId)
            ->where('equ_id', $team->equ_id)
            ->firstOrFail();

        // Note: We allow viewing the ticket even if not validated yet
        // The ticket will show the validation status
        
        // Generate QR code if it doesn't exist yet
        if (empty($registration->qr_code_path)) {
            try {
                $qrCodeService = app(\App\Services\QrCodeService::class);
                $qrPath = $qrCodeService->generateQrCodeForTeam(
                    $registration->equ_id,
                    $registration->reg_id
                );
                $registration->updateQuietly(['qr_code_path' => $qrPath]);
                $registration->refresh(); // Reload to get the updated qr_code_url accessor
            } catch (\Exception $e) {
                \Log::error('Failed to generate QR code in TeamController', [
                    'reg_id' => $registration->reg_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $teamMembers = $registration->team->users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
            ];
        });

        return Inertia::render('Team/RegistrationTicket', [
            'registration' => [
                'reg_id' => $registration->reg_id,
                'reg_dossard' => $registration->reg_dossard,
                'qr_code_url' => $registration->qr_code_url,
                'is_present' => $registration->is_present,
            ],
            'team' => [
                'equ_id' => $registration->team->equ_id,
                'equ_name' => $registration->team->equ_name,
                'equ_image' => $registration->team->equ_image ? asset('storage/' . $registration->team->equ_image) : null,
                'leader' => $registration->team->leader ? [
                    'name' => $registration->team->leader->first_name . ' ' . $registration->team->leader->last_name,
                    'email' => $registration->team->leader->email,
                ] : null,
                'members' => $teamMembers,
            ],
            'race' => [
                'race_id' => $registration->race->race_id,
                'race_name' => $registration->race->race_name,
                'race_distance' => $registration->race->race_distance,
            ],
            'raid' => [
                'raid_id' => $registration->race->raid->raid_id,
                'raid_name' => $registration->race->raid->raid_name,
                'raid_date_start' => $registration->race->raid->raid_date_start,
                'raid_city' => $registration->race->raid->raid_city,
                'raid_postal_code' => $registration->race->raid->raid_postal_code,
            ],
        ]);
    }
}
