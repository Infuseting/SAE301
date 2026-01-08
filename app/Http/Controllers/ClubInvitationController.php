<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\User;
use App\Models\ClubInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

/**
 * Controller for club invitation operations.
 * Handles sending, accepting, and rejecting club invitations.
 */
class ClubInvitationController extends Controller
{
    /**
     * Send a club invitation to a user or email.
     * 
     * @param Club $club
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Club $club, Request $request): JsonResponse
    {
        $user = auth()->user();

        // Check authorization - user must be club manager
        $isManager = $user->clubs()
            ->where('clubs.club_id', $club->club_id)
            ->wherePivot('role', 'manager')
            ->exists();

        if (!$isManager && $club->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $validated = $request->validate([
            'invitee_id' => 'nullable|exists:users,id',
            'email' => 'required_without:invitee_id|email',
            'role' => 'nullable|string|in:member,manager',
        ]);

        // Check if invitation already exists
        $existingInvitation = ClubInvitation::where('club_id', $club->club_id)
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

        // Check if user is already a member
        if ($validated['invitee_id'] ?? null) {
            $isMember = $club->members()
                ->where('users.id', $validated['invitee_id'])
                ->exists();

            if ($isMember) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.already_member'),
                ], 422);
            }
        }

        // Create invitation
        $invitation = ClubInvitation::create([
            'club_id' => $club->club_id,
            'inviter_id' => $user->id,
            'invitee_id' => $validated['invitee_id'] ?? null,
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'] ?? 'member',
        ]);

        // Send email notification
        // $this->sendInvitationEmail($invitation);

        return response()->json([
            'success' => true,
            'message' => __('messages.invitation_sent'),
            'invitation' => $invitation,
        ]);
    }

    /**
     * Accept a club invitation.
     * 
     * @param ClubInvitation $invitation
     * @return JsonResponse
     */
    public function accept(ClubInvitation $invitation): JsonResponse
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

        // Add user to club
        $invitation->club->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $invitation->role,
                'status' => 'approved',
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.invitation_accepted'),
        ]);
    }

    /**
     * Reject a club invitation.
     * 
     * @param ClubInvitation $invitation
     * @return JsonResponse
     */
    public function reject(ClubInvitation $invitation): JsonResponse
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
        $invitation = ClubInvitation::findByToken($token);

        if (!$invitation) {
            return redirect()->route('home')->with('error', __('messages.invalid_invitation'));
        }

        if ($invitation->isExpired()) {
            return redirect()->route('home')->with('error', __('messages.invitation_expired'));
        }

        $user = auth()->user();

        // If not logged in, redirect to login with return URL
        if (!$user) {
            return redirect()->route('login', ['redirect' => route('club.invitations.accept', $token)]);
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

        // Add to club
        $invitation->club->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $invitation->role,
                'status' => 'approved',
            ]
        ]);

        return redirect()->route('clubs.show', $invitation->club_id)
            ->with('success', __('messages.invitation_accepted'));
    }

    /**
     * Get pending invitations for a club (for club managers).
     * 
     * @param Club $club
     * @return JsonResponse
     */
    public function index(Club $club): JsonResponse
    {
        $user = auth()->user();

        // Check authorization
        $isManager = $user->clubs()
            ->where('clubs.club_id', $club->club_id)
            ->wherePivot('role', 'manager')
            ->exists();

        if (!$isManager && $club->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $invitations = ClubInvitation::where('club_id', $club->club_id)
            ->with(['inviter', 'invitee'])
            ->pending()
            ->get();

        return response()->json([
            'success' => true,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Cancel a pending invitation (for club managers).
     * 
     * @param ClubInvitation $invitation
     * @return JsonResponse
     */
    public function destroy(ClubInvitation $invitation): JsonResponse
    {
        $user = auth()->user();
        $club = $invitation->club;

        // Check authorization
        $isManager = $user->clubs()
            ->where('clubs.club_id', $club->club_id)
            ->wherePivot('role', 'manager')
            ->exists();

        if (!$isManager && $club->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.invitation_cancelled'),
        ]);
    }
}
