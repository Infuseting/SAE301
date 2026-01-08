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
            'email' => 'required|email',
            'role' => 'nullable|string|in:member,manager',
        ]);

        // Check if invitation already exists
        $existingInvitation = ClubInvitation::where('club_id', $club->club_id)
            ->where('email', $validated['email'])
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
            'invited_by' => $user->id,
            'email' => $validated['email'],
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
    public function accept(ClubInvitation $invitation)
    {
        $user = auth()->user();

        // Verify the invitation is for this user
        if ($invitation->email !== $user->email) {
            return back()->with('error', __('messages.invalid_invitation'));
        }

        if ($invitation->isExpired()) {
            return back()->with('error', __('messages.invitation_expired'));
        }

        if (!$invitation->isPending()) {
            return back()->with('error', __('messages.invitation_already_processed'));
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

        if (request()->wantsJson() && !request()->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => __('messages.invitation_accepted'),
            ]);
        }

        return redirect()->route('clubs.show', $invitation->club_id)
            ->with('success', __('messages.invitation_accepted'));
    }

    /**
     * Reject a club invitation.
     * 
     * @param ClubInvitation $invitation
     * @return JsonResponse
     */
    public function reject(ClubInvitation $invitation)
    {
        $user = auth()->user();

        // Verify the invitation is for this user
        if ($invitation->email !== $user->email) {
            return back()->with('error', __('messages.invalid_invitation'));
        }

        if (!$invitation->isPending()) {
            return back()->with('error', __('messages.invitation_already_processed'));
        }

        $invitation->reject();

        if (request()->wantsJson() && !request()->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => __('messages.invitation_rejected'),
            ]);
        }

        return back()->with('success', __('messages.invitation_rejected'));
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
