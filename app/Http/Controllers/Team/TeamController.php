<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Mail\TeamInvitation;
use App\Models\Invitation;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use App\Services\QrCodeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use OpenApi\Annotations as OA;

/**
 * Controller for managing teams.
 */
class TeamController extends Controller
{
    use ApiResponseTrait;

    public function create(): Response
    {
        return Inertia::render('Team/CreateTeam');
    }

    /**
     * Store a newly created team with users.
     *
     * Creates a new team, assigns the leader and teammates,
     * and adds the creator based on their selected role (leader or teammate).
     * Prevents duplicate entries and ensures the creator is not added multiple times.
     *
     * @OA\Post(
     *     path="/api/teams",
     *     tags={"Teams"},
     *     summary="Create a new team",
     *     description="Create a new team with optional teammates and email invitations",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=32, example="Les Aventuriers"),
     *             @OA\Property(property="image", type="string", nullable=true, example=null, description="Optional team image URL"),
     *             @OA\Property(
     *                  property="teammates",
     *                  type="array",
     *                  items=@OA\Items(type="object", @OA\Property(property="id", type="integer")),
     *                  description="List of teammates' IDs. At least one teammate is required if 'join_team' is not true.",
     *                  nullable=true
     *             ),
     *             @OA\Property(
     *                  property="emailInvites",
     *                  type="array",
     *                  items=@OA\Items(type="string", format="email"),
     *                  description="List of email invitations for the team.",
     *                  nullable=true
     *              ),
     *             @OA\Property(
     *                  property="join_team",
     *                  type="boolean",
     *                  description="Flag to indicate if the user is joining an existing team. Either 'join_team' or 'teammates' must be provided or both.",
     *                  nullable=true
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Team created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Team created successfully")
     *          )
     *      ),
     *     @OA\Response(
     *         response=302,
     *         description="Team created successfully, redirect to dashboard"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad Request - Validation error, either 'join_team' or 'teammates' must be provided",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Either 'join_team' or 'teammates' must be provided.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized - You must be authenticated to create a team.",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *         response=422,
     *         description="Validation error"
     *      ),
     *      security={{"apiAuth": {}}}
     * )
     */
    public function store(StoreTeamRequest $request)
    {
        // Le créateur est toujours le leader
        $creatorId = $request->user()->id;

        // Get validated data
        $validated = $request->validated();

        // Create the team with the creator as leader
        $team = Team::create([
            'equ_name' => $validated['name'],
            'equ_image' => $request->file('image') ? $request->file('image')->store('teams', 'public') : null,
            'user_id' => $creatorId,
        ]);

        $usersToAttach = [];

        if (!empty($validated['join_team'])) {
            // If the creator wants to join as a teammate, add them
            $usersToAttach[] = $creatorId;
        }

        // Add teammates to the team
        if (!empty($validated['teammates'])) {
            $teammateIds = array_map(static fn($teammate) => (int) $teammate['id'], $validated['teammates']);
            foreach ($teammateIds as $id) {
                if ($id !== $creatorId) {
                    $usersToAttach[] = $id;
                }
            }
        }
        if (!empty($usersToAttach)) {
            $team->users()->attach(array_unique($usersToAttach));
        }

        // Send email invitations
        if (!empty($validated['emailInvites'])) {
            foreach ($validated['emailInvites'] as $email) {
                $token = Str::random(64);

                Invitation::create([
                    'inviter_id' => $creatorId,
                    'invitee_id' => null,
                    'equ_id' => $team->equ_id,
                    'email' => $email,
                    'token' => $token,
                    'status' => 'pending',
                    'expires_at' => now()->addDays(7),
                ]);

                Mail::to($email)->send(new TeamInvitation($team->equ_name, $request->user()->name, $token));
            }
        }

        if ($request->wantsJson()) {
            return $this->successResponse([
                'team_id' => $team->equ_id,
                'team_name' => $team->equ_name,
            ], 'Team created successfully', 201);
        }

        return redirect()->route('home')->with('success', 'Équipe créée avec succès!');
    }

    /**
     * Display team details.
     *
     * @OA\Get(
     *     path="/api/teams/{teamId}",
     *     tags={"Teams"},
     *     summary="Get team details",
     *     description="Display detailed information about a specific team",
     *     @OA\Parameter(
     *         name="team",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Team details page"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Team not found"
     *     )
     * )
     */
    public function show(Team $team): Response
    {
        // Get team members
        $members = $team->users()->get()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ])->toArray();
        /*
         $members = $team->users()
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ])
            ->toArray();
        */

        // Get all users for invitation
        $users = User::all()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ])->toArray();

        // Get leader info
        $leader = $team->creator ?? null;

        return Inertia::render('Team/Show', [
            'team' => [
                'id' => $team->equ_id,
                'name' => $team->equ_name,
                'image' => $team->equ_image ? '/storage/' . $team->equ_image : null,
                'members' => $members,
                'created_at' => $team->created_at->format('d/m/Y'),
                'members_count' => count($members),
                'creator_id' => $team->user_id,
                'creator' => $leader ? [
                    'id' => $leader->id,
                    'name' => $leader->name,
                    'email' => $leader->email,
                ] : null,
            ],
            'users' => $users,
        ]);
    }

    /**
     * Send invitation email to a user.
     *
     * @OA\Post(
     *     path="/api/teams/{teamId}/invite",
     *     tags={"Teams"},
     *     summary="Send team invitation",
     *     description="Send an invitation to join the team via email",
     *     @OA\Parameter(
     *         name="team",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="receiver.invite@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Invitation sent successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Invitation sent successfully (web redirect)"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Only team leader can invite"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid email"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function inviteByEmail(Team $team, Request $request, User $user = null)
    {
        // Check authorization - only team leader can invite
        if ($request->user()->id !== $team->user_id) {
            $errorMessage = __('messages.team.invitation.unauthorized');
            $errorMessage = __('team.invitation.unauthorized');

            if ($request->wantsJson()) {
                return $this->forbiddenResponse($errorMessage);
            }

            return redirect()->back()->with('error', $errorMessage);
        }

        // Validate email
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $email = $user?->email ?? $validated['email'];
        $token = Str::random(64);

        // Create invitation
        Invitation::create([
            'inviter_id' => $request->user()->id,
            'invitee_id' => $user?->id,
            'equ_id' => $team->equ_id,
            'email' => $email,
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Send invitation email
        Mail::to($email)->send(new TeamInvitation($team->equ_name, $request->user()->name, $token));

        $successMessage = __('messages.team.invitation.sent_success');
        $successMessage = __('team.invitation.sent_success');

        // Return JSON response for API requests
        if ($request->wantsJson()) {
            return $this->successResponse([
                'token' => $token,
                'email' => $email,
                'expires_at' => now()->addDays(7)->toIso8601String(),
                'accept_url' => route('api.teams.invitations.accept', $token),
            ], $successMessage);
        }

        // Redirect for web requests
        return redirect()->back()->with('success', $successMessage);
    }

    /**
     * Display registration ticket with QR code.
     * Shows the QR code for a validated team registration.
     *
     * @OA\Get(
     *     path="/api/teams/{teamId}/registration/{registrationId}/ticket",
     *     tags={"Teams"},
     *     summary="Show team registration ticket",
     *     description="Display the registration ticket with QR code for race check-in",
     *     @OA\Parameter(
     *         name="team",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="registrationId",
     *         in="path",
     *         required=true,
     *         description="Registration ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration ticket page with QR code"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Must be team member"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Registration not found"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function showRegistrationTicket(Team $team, int $registrationId): Response
    {
        $user = auth()->user();

        // Check if user is team leader or member
        $isTeamLeader = $team->user_id === $user->id;
        $isTeamMember = $team->users()->where('users.id', $user->id)->exists();

        if (!$isTeamLeader && !$isTeamMember) {
            abort(403, 'Unauthorized. You must be a team member to view this ticket.');
        }

        // Get registration with race and raid information
        $registration = Registration::with(['race.raid', 'team.leader', 'team.users'])
            ->where('reg_id', $registrationId)
            ->where('equ_id', $team->equ_id)
            ->firstOrFail();

        // Note: We allow viewing the ticket even if not validated yet
        // The ticket will show the validation status

        // Generate QR code if it doesn't exist yet
        if (empty($registration->qr_code_path)) {
            try {
                $qrCodeService = app(QrCodeService::class);
                $qrPath = $qrCodeService->generateQrCodeForTeam(
                    $registration->equ_id,
                    $registration->reg_id
                );
                $registration->updateQuietly(['qr_code_path' => $qrPath]);
                $registration->refresh(); // Reload to get the updated qr_code_url accessor
            } catch (Exception) {
                //
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

    /**
     * Show the invitation acceptance page.
     *
     * @OA\Get(
     *     path="/api/teams/invitations/{token}",
     *     tags={"Teams"},
     *     summary="Show invitation acceptance page",
     *     description="Display the page to accept a team invitation via token",
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Invitation token",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation acceptance page"
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to login if not authenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invitation not found or expired"
     *     )
     * )
     */
    public function showAcceptInvitation($token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->status !== 'pending' || $invitation->expires_at < now()) {
            return redirect()->route('home')->with('error', 'Cette invitation a expiré ou a déjà été utilisée.');
        }

        // If not authenticated, store token in session and redirect to log in
        if (!auth()->check()) {
            session()->put('pending_invitation_token', $token);
            return redirect()->route('login')->with('info', 'Connectez-vous pour accepter l\'invitation.');
        }

        $team = Team::findOrFail($invitation->equ_id);

        return Inertia::render('Invitation/AcceptInvitation', [
            'invitation' => [
                'token' => $invitation->token,
                'inviterName' => $invitation->inviter->name,
            ],
            'team' => [
                'name' => $team->equ_name,
            ],
        ]);
    }

    /**
     * Accept an invitation via token.
     *
     * @OA\Post(
     *     path="/api/teams/invitations/{token}/accept",
     *     tags={"Teams"},
     *     summary="Accept team invitation",
     *     description="Accept a team invitation and join the team",
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Invitation token",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Invitation accepted, redirect to team page"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invitation not found or expired"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function acceptInvitation($token, Request $request)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->status !== 'pending' || $invitation->expires_at < now()) {
            $errorMessage = __('messages.team.invitation.expired');
            $errorMessage = __('team.invitation.expired');

            // Return JSON response for API requests
            if ($request->wantsJson()) {
                return $this->errorResponse($errorMessage, 410);
            }

            // Redirect for web requests
            return redirect()->route('home')->with('error', $errorMessage);
        }

        $team = Team::findOrFail($invitation->equ_id);

        // Add user to team if not already a member
        if (!$team->users()->wherePivot('id_users', auth()->id())->exists()) {
            $team->users()->attach(auth()->id());
        }

        $invitation->update(['status' => 'accepted', 'invitee_id' => auth()->id()]);

        $successMessage = __('messages.team.invitation.joined_success');
        $successMessage = __('team.invitation.joined_success');

        // Return JSON response for API requests
        if ($request->wantsJson()) {
            return $this->successResponse([
                'team_id' => $team->equ_id,
                'team_name' => $team->equ_name,
            ], $successMessage);
        }

        // Redirect for web requests
        return redirect()->route('teams.show', $team->equ_id)->with('success', $successMessage);
    }

    /**
     * Download QR code image with permission verification
     *
     * Only team members and leaders can download QR codes
     *
     * @OA\Get(
     *     path="/api/teams/{teamId}/registration/{registration}/qr-code",
     *     summary="Download QR code image",
     *     tags={"Teams"},
     *     @OA\Parameter(
     *         name="team",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="registration",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR code image"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Must be team member"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="QR code not found"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function downloadQrCode(Team $team, int $registrationId)
    {
        $user = auth()->user();

        // Check if user is team leader or member
        $isTeamLeader = $team->user_id === $user->id;
        $isTeamMember = $team->users()->where('users.id', $user->id)->exists();

        if (!$isTeamLeader && !$isTeamMember) {
            abort(403, 'Unauthorized. You must be a team member to access this QR code.');
        }

        // Get registration to verify it belongs to this team
        $registration = Registration::where('reg_id', $registrationId)
            ->where('equ_id', $team->equ_id)
            ->firstOrFail();

        // Check if QR code path exists
        if (empty($registration->qr_code_path)) {
            abort(404, 'QR code not found');
        }

        // Verify the file exists on disk
        if (!Storage::disk('public')->exists($registration->qr_code_path)) {
            abort(404, 'QR code file not found');
        }

        // Return the file as a response
        return response(
            Storage::disk('public')->get($registration->qr_code_path),
            200,
            [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="qr_code_' . $registration->reg_id . '.svg"'
            ]
        );
    }
}
