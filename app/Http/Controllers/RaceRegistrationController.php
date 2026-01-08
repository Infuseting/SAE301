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

        if ($existingRegistration) {
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

        // Create registration
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

        // If temporary team, send invitations
        if ($validated['is_temporary_team']) {
            $this->sendTemporaryTeamInvitations($registration, $validated['temporary_team_data'], $user);
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

            $invitation = \App\Models\TeamInvitation::create([
                'registration_id' => $registration->reg_id,
                'inviter_id' => $inviter->id,
                'invitee_id' => $member['user_id'] ?? null,
                'email' => $member['email'] ?? null,
            ]);

            // Send email notification (to be implemented)
            // Mail::to($invitation->email ?? $invitation->invitee->email)->send(new TeamInvitationMail($invitation));
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

        // Check authorization
        if ($registration->user_id !== $user->id) {
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
}
