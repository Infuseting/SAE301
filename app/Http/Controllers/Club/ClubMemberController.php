<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * @OA\Tag(
 *     name="Club Members",
 *     description="Club member management endpoints"
 * )
 */
class ClubMemberController extends Controller
{
    /**
     * User requests to join a club.
     *
     * @OA\Post(
     *     path="/api/clubs/{id}/join",
     *     tags={"Club Members"},
     *     summary="Request to join a club",
     *     description="Authenticated user requests to join a club",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Join request sent successfully"
     *     ),
     *     @OA\Response(response=400, description="Already a member or request pending"),
     *     @OA\Response(response=403, description="Club not approved"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function requestJoin(Club $club): RedirectResponse
    {
        $user = auth()->user();

        // Check if club is approved
        if (!$club->is_approved) {
            return back()->with('error', __('messages.club_not_approved'));
        }

        // Check if already a member
        if ($club->hasMember($user)) {
            return back()->with('error', __('messages.already_member'));
        }

        // Check if request already pending
        $existingRequest = $club->pendingRequests()->where('user_id', $user->id)->exists();
        if ($existingRequest) {
            return back()->with('error', __('messages.request_already_pending'));
        }

        // Create join request
        $club->members()->attach($user->id, [
            'role' => 'member',
            'status' => 'pending',
        ]);

        activity()
            ->performedOn($club)
            ->causedBy($user)
            ->log('Join request sent');

        return back()->with('success', __('messages.join_request_sent'));
    }

    /**
     * Club manager approves a join request.
     *
     * @OA\Post(
     *     path="/api/clubs/{clubId}/members/{userId}/approve",
     *     tags={"Club Members"},
     *     summary="Approve a join request",
     *     description="Club manager approves a pending join request",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Join request approved"
     *     ),
     *     @OA\Response(response=403, description="Not authorized"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function approveJoin(Club $club, User $user): RedirectResponse
    {
        // Only club managers can approve
        if (!$club->hasManager(auth()->user())) {
            abort(403, 'Only club managers can approve join requests');
        }

        // Update the pivot status using allMembers() to include pending requests
        $club->allMembers()->updateExistingPivot($user->id, [
            'status' => 'approved',
        ]);

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->withProperties(['approved_user' => $user->name])
            ->log('Join request approved');

        return back()->with('success', __('messages.join_request_approved', ['name' => $user->name]));
    }

    /**
     * Club manager rejects a join request.
     *
     * @OA\Post(
     *     path="/api/clubs/{clubId}/members/{userId}/reject",
     *     tags={"Club Members"},
     *     summary="Reject a join request",
     *     description="Club manager rejects a pending join request",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Join request rejected"
     *     ),
     *     @OA\Response(response=403, description="Not authorized"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function rejectJoin(Club $club, User $user): RedirectResponse
    {
        // Only club managers can reject
        if (!$club->hasManager(auth()->user())) {
            abort(403, 'Only club managers can reject join requests');
        }

        // Remove the pending request using allMembers() to include pending entries
        $club->allMembers()->detach($user->id);

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->withProperties(['rejected_user' => $user->name])
            ->log('Join request rejected');

        return back()->with('success', __('messages.join_request_rejected', ['name' => $user->name]));
    }

    /**
     * Club manager removes a member.
     *
     * @OA\Delete(
     *     path="/api/clubs/{clubId}/members/{userId}",
     *     tags={"Club Members"},
     *     summary="Remove a member from club",
     *     description="Club manager removes a member from the club",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member removed successfully"
     *     ),
     *     @OA\Response(response=403, description="Not authorized"),
     *     @OA\Response(response=404, description="Member not found")
     * )
     */
    public function removeMember(Club $club, User $user): RedirectResponse
    {
        // Only club managers can remove members
        if (!$club->hasManager(auth()->user())) {
            abort(403, 'Only club managers can remove members');
        }

        // Cannot remove managers
        if ($club->hasManager($user)) {
            return back()->with('error', __('messages.cannot_remove_manager'));
        }

        $club->members()->detach($user->id);

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->withProperties(['removed_user' => $user->name])
            ->log('Member removed from club');

        return back()->with('success', __('messages.member_removed', ['name' => $user->name]));
    }

    /**
     * User leaves a club.
     *
     * @OA\Post(
     *     path="/api/clubs/{id}/leave",
     *     tags={"Club Members"},
     *     summary="Leave a club",
     *     description="Authenticated user leaves a club they are a member of",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Left club successfully"
     *     ),
     *     @OA\Response(response=400, description="Cannot leave (e.g., last manager)"),
     *     @OA\Response(response=404, description="Club not found")
     * )
     */
    public function leave(Club $club): RedirectResponse
    {
        $user = auth()->user();

        // Check if user is a member
        if (!$club->hasMember($user)) {
            return back()->with('error', __('messages.not_a_member'));
        }

        // Prevent last manager from leaving
        if ($club->hasManager($user) && $club->managers()->count() === 1) {
            return back()->with('error', __('messages.last_manager_cannot_leave'));
        }

        // If user was a manager, check if they should keep the responsable-club role
        // (only if they manage other clubs)
        if ($club->hasManager($user)) {
            $otherManagedClubs = $user->clubs()
                ->wherePivot('role', 'manager')
                ->wherePivot('status', 'approved')
                ->where('clubs.club_id', '!=', $club->club_id)
                ->count();
            
            if ($otherManagedClubs === 0 && $user->hasRole('responsable-club')) {
                $user->removeRole('responsable-club');
            }
        }

        $club->members()->detach($user->id);

        activity()
            ->performedOn($club)
            ->causedBy($user)
            ->log('User left club');

        return redirect()->route('clubs.index')
            ->with('success', __('messages.left_club'));
    }

    /**
     * Promote a club member to manager.
     *
     * @OA\Post(
     *     path="/api/clubs/{clubId}/members/{userId}/promote",
     *     tags={"Club Members"},
     *     summary="Promote a member to manager",
     *     description="Club manager promotes a member to manager role",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member promoted to manager"
     *     ),
     *     @OA\Response(response=403, description="Not authorized"),
     *     @OA\Response(response=404, description="Member not found")
     * )
     */
    public function promoteToManager(Club $club, User $user): RedirectResponse
    {
        // Only club managers can promote
        if (!$club->hasManager(auth()->user())) {
            abort(403, 'Only club managers can promote members');
        }

        // Check if user is an approved member
        $membership = $club->members()
            ->where('user_id', $user->id)
            ->wherePivot('status', 'approved')
            ->first();

        if (!$membership) {
            return back()->with('error', __('messages.user_not_member'));
        }

        // Update the pivot role to manager
        $club->members()->updateExistingPivot($user->id, [
            'role' => 'manager',
        ]);

        // Assign the responsable-club role if not already assigned
        if (!$user->hasRole('responsable-club')) {
            $user->assignRole('responsable-club');
        }

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->withProperties(['promoted_user' => $user->name])
            ->log('Member promoted to manager');

        return back()->with('success', __('messages.member_promoted', ['name' => $user->name]));
    }

    /**
     * Demote a club manager to regular member.
     *
     * @OA\Post(
     *     path="/api/clubs/{clubId}/members/{userId}/demote",
     *     tags={"Club Members"},
     *     summary="Demote a manager to member",
     *     description="Club manager demotes another manager to regular member",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         description="Club ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Manager demoted to member"
     *     ),
     *     @OA\Response(response=403, description="Not authorized"),
     *     @OA\Response(response=400, description="Cannot demote last manager")
     * )
     */
    public function demoteFromManager(Club $club, User $user): RedirectResponse
    {
        // Only club managers can demote
        if (!$club->hasManager(auth()->user())) {
            abort(403, 'Only club managers can demote managers');
        }

        // Cannot demote yourself if you're the last manager
        if ($club->managers()->count() === 1) {
            return back()->with('error', __('messages.cannot_demote_last_manager'));
        }

        // Update the pivot role to member
        $club->members()->updateExistingPivot($user->id, [
            'role' => 'member',
        ]);

        // Check if user manages other clubs before removing the role
        $otherManagedClubs = $user->clubs()
            ->wherePivot('role', 'manager')
            ->wherePivot('status', 'approved')
            ->where('clubs.club_id', '!=', $club->club_id)
            ->count();

        if ($otherManagedClubs === 0 && $user->hasRole('responsable-club')) {
            $user->removeRole('responsable-club');
        }

        activity()
            ->performedOn($club)
            ->causedBy(auth()->user())
            ->withProperties(['demoted_user' => $user->name])
            ->log('Manager demoted to member');

        return back()->with('success', __('messages.manager_demoted', ['name' => $user->name]));
    }
}
