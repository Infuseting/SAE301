<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Models\TeamInvitation;
use App\Models\RaceRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\TeamInvitationMail;
use App\Mail\TeamInvitationNewUserMail;

/**
 * Controller for team invitation operations.
 * Handles sending, accepting, and rejecting team invitations.
 */
class TeamInvitationController extends Controller
{
    /**
     * Send a team invitation to a user or email.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'team_id' => 'nullable|exists:teams,equ_id',
            'registration_id' => 'nullable|exists:race_registrations,id',
            'invitee_id' => 'nullable|exists:users,id',
            'email' => 'required_without:invitee_id|email',
        ]);

        // Check authorization - must be team leader or registration creator
        if ($validated['team_id']) {
            $team = Team::find($validated['team_id']);
            if ($team->adh_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.unauthorized'),
                ], 403);
            }
        }

        if ($validated['registration_id']) {
            $registration = RaceRegistration::find($validated['registration_id']);
            if ($registration->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.unauthorized'),
                ], 403);
            }
        }

        // Check if invitation already exists
        $existingInvitation = TeamInvitation::where('team_id', $validated['team_id'] ?? null)
            ->where('registration_id', $validated['registration_id'] ?? null)
            ->where(function ($q) use ($validated) {
                if ($validated['invitee_id'] ?? null) {
                    $q->where('invitee_id', $validated['invitee_id']);
                } else {
                    $q->where('email', $validated['email']);
                }
            })
            ->pending()
            ->first();

        if ($existingInvitation) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invitation_already_sent'),
            ], 422);
        }

        // Create invitation
        $invitation = TeamInvitation::create([
            'team_id' => $validated['team_id'] ?? null,
            'registration_id' => $validated['registration_id'] ?? null,
            'inviter_id' => $user->id,
            'invitee_id' => $validated['invitee_id'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        // Send email notification
        $this->sendInvitationEmail($invitation);

        return response()->json([
            'success' => true,
            'message' => __('messages.invitation_sent'),
            'invitation' => $invitation,
        ]);
    }

    /**
     * Send invitation email.
     */
    private function sendInvitationEmail(TeamInvitation $invitation): void
    {
        $recipientEmail = $invitation->email ?? $invitation->invitee?->email;

        if (!$recipientEmail) {
            return;
        }

        // Use different email template based on whether user exists
        if ($invitation->invitee_id) {
            // User exists - send standard invitation
            // Mail::to($recipientEmail)->queue(new TeamInvitationMail($invitation));
        } else {
            // User doesn't exist - send invitation with account creation link
            // Mail::to($recipientEmail)->queue(new TeamInvitationNewUserMail($invitation));
        }
    }

    /**
     * Accept a team invitation (for authenticated users).
     * 
     * @param TeamInvitation $invitation
     * @return JsonResponse
     */
    public function accept(TeamInvitation $invitation): JsonResponse
    {
        $user = auth()->user();

        // Verify the invitation is for this user
        if ($invitation->invitee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_invitation'),
            ], 403);
        }

        if ($invitation->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invitation_expired'),
            ], 422);
        }

        if (!$invitation->isPending()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invitation_already_processed'),
            ], 422);
        }

        // Accept the invitation
        $invitation->accept();

        // Add user to team if permanent team
        if ($invitation->team_id) {
            $invitation->team->users()->attach($user->id);
        }

        // Update temporary team data if registration-based
        if ($invitation->registration_id) {
            $this->updateTemporaryTeamMemberStatus($invitation, 'confirmed');
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.invitation_accepted'),
        ]);
    }

    /**
     * Reject a team invitation.
     * 
     * @param TeamInvitation $invitation
     * @return JsonResponse
     */
    public function reject(TeamInvitation $invitation): JsonResponse
    {
        $user = auth()->user();

        // Verify the invitation is for this user
        if ($invitation->invitee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_invitation'),
            ], 403);
        }

        if (!$invitation->isPending()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invitation_already_processed'),
            ], 422);
        }

        $invitation->reject();

        return response()->json([
            'success' => true,
            'message' => __('messages.invitation_rejected'),
        ]);
    }

    /**
     * Accept invitation via token (for email links).
     * 
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acceptViaToken(string $token)
    {
        $invitation = TeamInvitation::findByToken($token);

        if (!$invitation) {
            return redirect()->route('home')->with('error', __('messages.invalid_invitation'));
        }

        if ($invitation->isExpired()) {
            return redirect()->route('home')->with('error', __('messages.invitation_expired'));
        }

        $user = auth()->user();

        // If not logged in, redirect to login with return URL
        if (!$user) {
            return redirect()->route('login', ['redirect' => route('invitations.accept', $token)]);
        }

        // If email invite, check if this user's email matches
        if ($invitation->isEmailInvitation()) {
            if ($user->email !== $invitation->email) {
                return redirect()->route('home')->with('error', __('messages.invitation_wrong_email'));
            }
            // Update invitation with user id
            $invitation->update(['invitee_id' => $user->id]);
        }

        // Accept the invitation
        $invitation->accept();

        // Add to team
        if ($invitation->team_id) {
            $invitation->team->users()->syncWithoutDetaching([$user->id]);
        }

        if ($invitation->registration_id) {
            $this->updateTemporaryTeamMemberStatus($invitation, 'confirmed');
        }

        return redirect()->route('home')->with('success', __('messages.invitation_accepted'));
    }

    /**
     * Update temporary team member status in registration.
     */
    private function updateTemporaryTeamMemberStatus(TeamInvitation $invitation, string $status): void
    {
        $registration = $invitation->registration;
        if (!$registration || !$registration->is_temporary_team) {
            return;
        }

        $teamData = $registration->temporary_team_data ?? [];
        $email = $invitation->email ?? $invitation->invitee?->email;

        foreach ($teamData as &$member) {
            if (
                (isset($member['user_id']) && $member['user_id'] == $invitation->invitee_id) ||
                (isset($member['email']) && $member['email'] == $email)
            ) {
                $member['status'] = $status;
                if ($invitation->invitee_id) {
                    $member['user_id'] = $invitation->invitee_id;
                }
            }
        }

        $registration->update(['temporary_team_data' => $teamData]);
    }
}
