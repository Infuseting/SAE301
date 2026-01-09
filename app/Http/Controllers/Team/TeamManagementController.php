<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Registration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * Controller for managing teams.
 * 
 * Team leaders can manage their own teams.
 * Administrators can manage all teams.
 * 
 * @OA\Tag(
 *     name="Team Management",
 *     description="Endpoints for team management (admin and team leaders)"
 * )
 */
class TeamManagementController extends Controller
{
    /**
     * Display the team management page.
     * 
     * Shows teams based on user role:
     * - Admin: All teams
     * - Team Leader: Only teams where user is the leader
     * 
     * @OA\Get(
     *     path="/teams/management",
     *     tags={"Team Management"},
     *     summary="Get teams for management",
     *     description="Returns paginated list of teams based on user role",
     *     @OA\Response(
     *         response=200,
     *         description="Team management page with teams list"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        // Build query based on role
        $query = Team::with(['leader', 'registrations.race.raid', 'users']);

        if (!$isAdmin) {
            // Team leaders only see their teams
            $query->where('user_id', $user->id);
        }

        // Get teams with pagination
        $teams = $query->orderBy('created_at', 'desc')->paginate(10);

        // Transform teams for frontend
        $transformedTeams = $teams->map(function ($team) {
            return [
                'id' => $team->equ_id,
                'name' => $team->equ_name,
                'image' => $team->equ_image,
                'leader' => [
                    'id' => $team->leader->id ?? null,
                    'name' => $team->leader->name ?? null,
                    'email' => $team->leader->email ?? null,
                ],
                'members' => $team->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                })->toArray(),
                'members_count' => $team->users->count(),
                'registrations_count' => $team->registrations->count(),
                'registrations' => $team->registrations->map(function ($registration) {
                    return [
                        'id' => $registration->reg_id,
                        'race' => [
                            'id' => $registration->race->cou_id ?? null,
                            'name' => $registration->race->cou_name ?? null,
                            'raid' => [
                                'id' => $registration->race->raid->rai_id ?? null,
                                'name' => $registration->race->raid->rai_name ?? null,
                            ],
                        ],
                    ];
                }),
                'created_at' => $team->created_at->format('d/m/Y'),
            ];
        });

        return Inertia::render('Team/TeamManagement', [
            'teams' => [
                'data' => $transformedTeams,
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
            ],
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Update team information.
     * 
     * Only team leader or admin can update.
     * 
     * @OA\Put(
     *     path="/teams/{team}",
     *     tags={"Team Management"},
     *     summary="Update team",
     *     description="Update team information including name, image, and members",
     *     @OA\Parameter(
     *         name="team",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", maxLength=32),
     *                 @OA\Property(property="image", type="file"),
     *                 @OA\Property(
     *                     property="add_members",
     *                     type="array",
     *                     @OA\Items(type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="remove_members",
     *                     type="array",
     *                     @OA\Items(type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Team updated successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function update(Request $request, Team $team)
    {
        $user = Auth::user();
        
        // Check authorization
        if (!$user->hasRole('admin') && $team->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:32',
            'image' => 'nullable|image|max:2048',
            'add_members' => 'nullable|array',
            'add_members.*' => 'integer|exists:users,id',
            'remove_members' => 'nullable|array',
            'remove_members.*' => 'integer|exists:users,id',
        ]);

        $team->equ_name = $validated['name'];
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($team->equ_image) {
                \Storage::disk('public')->delete($team->equ_image);
            }
            $team->equ_image = $request->file('image')->store('teams', 'public');
        }

        $team->save();

        // Handle member additions
        if (!empty($validated['add_members'])) {
            foreach ($validated['add_members'] as $userId) {
                // Check if user is not already a member
                if (!$team->users()->where('id_users', $userId)->exists()) {
                    $team->users()->attach($userId);
                }
            }
        }

        // Handle member removals
        if (!empty($validated['remove_members'])) {
            $team->users()->detach($validated['remove_members']);
        }

        activity()
            ->causedBy($user)
            ->performedOn($team)
            ->log('updated_team');

        return back()->with('success', __('messages.team_updated_successfully'));
    }

    /**
     * Delete a team.
     * 
     * Only team leader or admin can delete.
     * Cannot delete if team has active registrations.
     * 
     * @OA\Delete(
     *     path="/teams/{team}",
     *     tags={"Team Management"},
     *     summary="Delete team",
     *     description="Delete a team if it has no active registrations",
     *     @OA\Parameter(
     *         name="team",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Team deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete team with active registrations"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function destroy(Team $team)
    {
        $user = Auth::user();
        
        // Check authorization
        if (!$user->hasRole('admin') && $team->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // Check if team has registrations
        if ($team->registrations()->count() > 0) {
            return back()->withErrors([
                'team' => __('messages.cannot_delete_team_with_registrations')
            ]);
        }

        activity()
            ->causedBy($user)
            ->performedOn($team)
            ->log('deleted_team');

        $team->delete();

        return redirect()->route('teams.management')->with('success', __('messages.team_deleted_successfully'));
    }

    /**
     * Remove a member from the team.
     * 
     * Only team leader or admin can remove members.
     * 
     * @OA\Post(
     *     path="/teams/{team}/remove-member",
     *     tags={"Team Management"},
     *     summary="Remove team member",
     *     description="Remove a specific member from the team",
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
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Member removed successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     security={{"apiAuth": {}}}
     * )
     */
    public function removeMember(Request $request, Team $team)
    {
        $user = Auth::user();
        
        // Check authorization
        if (!$user->hasRole('admin') && $team->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $team->users()->detach($validated['user_id']);

        activity()
            ->causedBy($user)
            ->performedOn($team)
            ->log('removed_team_member');

        return back()->with('success', __('messages.member_removed_successfully'));
    }
}
