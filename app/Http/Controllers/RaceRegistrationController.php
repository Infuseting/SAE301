<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\Team;
use App\Models\User;
use App\Models\RaceRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for race registration operations.
 * Handles eligibility checking, team validation, and registration processing.
 */
class RaceRegistrationController extends Controller
{
    /**
     * Check user eligibility to register for a race.
     * Returns eligibility status, reasons if ineligible, and user's compatible teams.
     * 
     * @param Race $race
     * @return JsonResponse
     */
    public function checkEligibility(Race $race): JsonResponse
    {
        $user = auth()->user();

        // Check if registration is open
        $eligibility = [
            'can_register' => true,
            'reasons' => [],
            'registration_open' => $race->isOpen(),
            'registration_upcoming' => $race->isRegistrationUpcoming(),
            'has_valid_credentials' => false,
            'compatible_teams' => [],
        ];

        // Check registration period
        if (!$race->isOpen()) {
            $eligibility['can_register'] = false;
            if ($race->isRegistrationUpcoming()) {
                $eligibility['reasons'][] = [
                    'type' => 'registration_not_started',
                    'message' => __('messages.registration_not_started'),
                ];
            } else {
                $eligibility['reasons'][] = [
                    'type' => 'registration_closed',
                    'message' => __('messages.registration_closed'),
                ];
            }
        }

        // Check licence/PPS credentials
        if ($user) {
            $eligibility['has_valid_credentials'] = $user->hasValidCredentials();

            if (!$eligibility['has_valid_credentials']) {
                $eligibility['can_register'] = false;
                $eligibility['reasons'][] = [
                    'type' => 'no_credentials',
                    'message' => __('messages.need_valid_credentials'),
                ];
            }

            // Get user's compatible teams
            $eligibility['compatible_teams'] = $this->getCompatibleTeams($user, $race);
        } else {
            $eligibility['can_register'] = false;
            $eligibility['reasons'][] = [
                'type' => 'not_authenticated',
                'message' => __('messages.login_required'),
            ];
        }

        return response()->json($eligibility);
    }

    /**
     * Get user's teams that are compatible with race requirements.
     * 
     * @param User $user
     * @param Race $race
     * @return array
     */
    private function getCompatibleTeams(User $user, Race $race): array
    {
        $teams = $user->teams()->with('users', 'leader')->get();
        $result = [];

        foreach ($teams as $team) {
            $validation = $team->validateForRace($race);

            $result[] = [
                'id' => $team->equ_id,
                'name' => $team->equ_name,
                'image' => $team->equ_image,
                'member_count' => $team->users->count(),
                'is_compatible' => $validation['valid'],
                'errors' => $validation['errors'],
                'leader' => [
                    'id' => $team->leader?->id,
                    'name' => $team->leader?->name,
                ],
                'members' => $team->users->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'profile_photo_url' => $u->profile_photo_url,
                    ];
                }),
            ];
        }

        return $result;
    }

    /**
     * Register for a race with a team.
     * 
     * @param Race $race
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Race $race, Request $request): JsonResponse
    {
        $user = auth()->user();

        // Validate request
        $validated = $request->validate([
            'team_id' => 'nullable|exists:teams,equ_id',
            'is_temporary_team' => 'required|boolean',
            'temporary_team_data' => 'required_if:is_temporary_team,true|array',
            'temporary_team_data.*.user_id' => 'nullable|exists:users,id',
            'temporary_team_data.*.email' => 'required_without:temporary_team_data.*.user_id|email',
            'is_creator_participating' => 'required|boolean',
        ]);

        // Check if already registered
        $existingRegistration = RaceRegistration::where('race_id', $race->race_id)
            ->where('user_id', $user->id)
            ->first();

        // If active registration exists, block
        if ($existingRegistration && $existingRegistration->status !== 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => __('messages.already_registered'),
            ], 422);
        }

        // Check eligibility
        if (!$race->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.registration_closed'),
            ], 422);
        }

        if (!$user->hasValidCredentials()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.need_valid_credentials'),
            ], 422);
        }

        // Validate team if permanent
        if (!$validated['is_temporary_team'] && $validated['team_id']) {
            $team = Team::find($validated['team_id']);
            $validation = $team->validateForRace($race);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => implode(', ', $validation['errors']),
                ], 422);
            }
        }

        // Update existing cancelled registration or create new one
        if ($existingRegistration && $existingRegistration->status === 'cancelled') {
            // Reuse cancelled registration
            $existingRegistration->update([
                'equ_id' => $validated['is_temporary_team'] ? null : $validated['team_id'],
                'is_team_leader' => true,
                'is_temporary_team' => $validated['is_temporary_team'],
                'temporary_team_data' => $validated['is_temporary_team'] ? $validated['temporary_team_data'] : null,
                'is_creator_participating' => $validated['is_creator_participating'],
                'status' => 'pending',
            ]);
            $registration = $existingRegistration;
        } else {
            // Create new registration
            $registration = RaceRegistration::create([
                'race_id' => $race->race_id,
                'equ_id' => $validated['is_temporary_team'] ? null : $validated['team_id'],
                'user_id' => $user->id,
                'is_team_leader' => true,
                'is_temporary_team' => $validated['is_temporary_team'],
                'temporary_team_data' => $validated['is_temporary_team'] ? $validated['temporary_team_data'] : null,
                'is_creator_participating' => $validated['is_creator_participating'],
                'status' => 'pending',
            ]);
        }

        // Send notifications to team members
        if ($validated['is_temporary_team']) {
            // For temporary teams, send invitations
            $this->sendTemporaryTeamInvitations($registration, $validated['temporary_team_data'], $user);
        } else if ($validated['team_id']) {
            // For permanent teams, notify members
            $this->notifyPermanentTeamMembers($registration, $user);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.registration_successful'),
            'registration' => $registration,
        ]);
    }

    /**
     * Send invitations to temporary team members.
     * 
     * @param RaceRegistration $registration
     * @param array $members
     * @param User $inviter
     */
    private function sendTemporaryTeamInvitations(RaceRegistration $registration, array $members, User $inviter): void
    {
        foreach ($members as $member) {
            // Skip if this is the creator
            if (isset($member['user_id']) && $member['user_id'] == $inviter->id) {
                continue;
            }

            // Check if invitation already exists for this email and registration
            $exists = \App\Models\TemporaryTeamInvitation::where('registration_id', $registration->reg_id)
                ->where('email', $member['email'])
                ->exists();

            if ($exists) {
                continue;
            }

            // Create invitation
            $invitation = \App\Models\TemporaryTeamInvitation::create([
                'registration_id' => $registration->reg_id,
                'inviter_id' => $inviter->id,
                'email' => $member['email'],
            ]);

            // Send email
            try {
                \Mail::to($invitation->email)->send(new \App\Mail\TeamInvitationMail($invitation));
            } catch (\Exception $e) {
                \Log::error('Failed to send team invitation email', [
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify permanent team members about race registration.
     * 
     * @param RaceRegistration $registration
     * @param User $creator
     */
    private function notifyPermanentTeamMembers(RaceRegistration $registration, User $creator): void
    {
        $team = $registration->team;
        if (!$team) {
            return;
        }

        // Get all team members except creator
        $members = $team->users;

        foreach ($members as $member) {
            // Skip creator (they already know they registered)
            if ($member->id === $creator->id) {
                continue;
            }

            // Create invitation for tracking
            $invitation = \App\Models\TemporaryTeamInvitation::create([
                'registration_id' => $registration->reg_id,
                'inviter_id' => $creator->id,
                'email' => $member->email,
            ]);

            // Send notification email
            try {
                \Mail::to($member->email)->send(new \App\Mail\TeamInvitationMail($invitation));
            } catch (\Exception $e) {
                \Log::error('Failed to send team member notification', [
                    'registration_id' => $registration->reg_id,
                    'member_id' => $member->id,
                    'email' => $member->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Cancel a registration.
     * 
     * @param RaceRegistration $registration
     * @return JsonResponse
     */
    public function cancel(RaceRegistration $registration): JsonResponse
    {
        $user = auth()->user();

        // Check authorization: creator or team leader
        $isLeader = (int) $registration->user_id === (int) $user->id ||
            ($registration->team && (int) $registration->team->adh_id === (int) $user->id);

        if (!$isLeader) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $registration->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => __('messages.registration_cancelled'),
        ]);
    }

    /**
     * Show edit form for registration.
     */
    public function edit(RaceRegistration $registration)
    {
        $user = auth()->user();

        // Check authorization
        if ($registration->user_id !== $user->id || !$registration->canEdit()) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Registration/Edit', [
            'registration' => [
                'id' => $registration->reg_id,
                'race_id' => $registration->race_id,
                'race_name' => $registration->race->race_name,
                'team_data' => $registration->temporary_team_data,
                'is_creator_participating' => $registration->is_creator_participating,
            ],
        ]);
    }

    /**
     * Update registration team members.
     */
    public function update(Request $request, RaceRegistration $registration)
    {
        $user = auth()->user();

        // Check authorization: creator or team leader
        $isLeader = (int) $registration->user_id === (int) $user->id ||
            ($registration->team && (int) $registration->team->adh_id === (int) $user->id);

        if (!$isLeader || !$registration->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $validated = $request->validate([
            'temporary_team_data' => ['required', 'array'],
            'temporary_team_data.*.email' => ['required', 'email'],
            'temporary_team_data.*.name' => ['nullable', 'string'],
            'temporary_team_data.*.user_id' => ['nullable', 'integer'],
            'is_creator_participating' => ['required', 'boolean'],
        ]);

        // Logic check: who is new?
        $oldMembers = $registration->temporary_team_data ?? [];
        $newMembers = $validated['temporary_team_data'];

        // Determine new invitations to send
        foreach ($newMembers as $newMember) {
            $isNew = true;
            foreach ($oldMembers as $oldMember) {
                if ($newMember['email'] === $oldMember['email']) {
                    $isNew = false;
                    break;
                }
            }

            if ($isNew) {
                // If the member doesn't have a status yet, set it to pending
                // (sendTemporaryTeamInvitations will handle creation)
            }
        }

        // Update registration
        $registration->update([
            'temporary_team_data' => $newMembers,
            'is_creator_participating' => $validated['is_creator_participating'],
        ]);

        // Send invitations to new members
        $this->sendTemporaryTeamInvitations($registration, $newMembers, $user);

        return response()->json([
            'success' => true,
            'message' => 'Inscription mise à jour avec succès',
            'registration' => $registration,
        ]);
    }

    /**
     * Resend invitation to a specific email.
     */
    public function resendInvitation(RaceRegistration $registration, string $email)
    {
        $user = auth()->user();

        // Check authorization
        if ($registration->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Find existing invitation
        $invitation = \App\Models\TemporaryTeamInvitation::where('registration_id', $registration->reg_id)
            ->where('email', $email)
            ->first();

        if ($invitation) {
            // Update expiration
            $invitation->update([
                'expires_at' => now()->addDays(7),
                'status' => 'pending',
            ]);
        } else {
            // Create new invitation
            $invitation = \App\Models\TemporaryTeamInvitation::create([
                'registration_id' => $registration->reg_id,
                'inviter_id' => $user->id,
                'email' => $email,
            ]);
        }

        // Send email
        try {
            \Mail::to($email)->send(new \App\Mail\TeamInvitationMail($invitation));
        } catch (\Exception $e) {
            \Log::error('Failed to resend invitation', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation renvoyée avec succès',
        ]);
    }

    /**
     * Leave team (for non-leader members).
     */
    public function leaveTeam(RaceRegistration $registration)
    {
        $user = auth()->user();

        // If user is the leader/creator, they should use cancel instead of leaving
        $isLeader = (int) $registration->user_id === (int) $user->id ||
            ($registration->team && (int) $registration->team->adh_id === (int) $user->id);

        if ($isLeader) {
            return $this->cancel($registration);
        }

        // Check if user is a member of this registration
        $isMember = false;

        // CASE 1: Permanent team
        if (!$registration->is_temporary_team && $registration->team) {
            $isMember = $registration->team->users()->where('users.id', $user->id)->exists();
            if ($isMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pour quitter une équipe permanente, veuillez contacter votre chef d\'équipe.',
                ], 403);
            }
        }

        // CASE 2: Temporary team
        $teamData = $registration->temporary_team_data ?? [];

        foreach ($teamData as $index => $member) {
            if (isset($member['user_id']) && $member['user_id'] == $user->id) {
                $isMember = true;
                // Remove member from team
                unset($teamData[$index]);
                break;
            }
        }

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas membre de cette équipe',
            ], 403);
        }

        // Reindex array and update registration
        $registration->update([
            'temporary_team_data' => array_values($teamData)
        ]);

        // Mark invitation as rejected
        \App\Models\TemporaryTeamInvitation::where('registration_id', $registration->reg_id)
            ->where('email', $user->email)
            ->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Vous avez quitté l\'équipe avec succès',
        ]);
    }
}
