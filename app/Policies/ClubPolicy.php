<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for managing Club access control
 */
class ClubPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Everyone can view clubs (including guests)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Club $club): bool
    {
        // Everyone can view a specific club (including guests)
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Only adherents (licensed members) and administrators can create clubs.
     */
    public function create(User $user): bool
    {
        // Admin can always create clubs
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Adherents can create clubs (they have a valid licence)
        if ($user->hasRole('adherent')) {
            return true;
        }
        
        // Simple users and other roles cannot create clubs
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Club $club): bool
    {        
        // Admin can update any club
        $userRoles = $user->roles->pluck('name')->toArray();
        \Log::info("ClubPolicy::update - User roles", [
            'user_id' => $user->id,
            'roles' => $userRoles,
            'hasAdminRole' => $user->hasRole('admin'),
        ]);
        
        if ($user->hasRole('admin')) {
            \Log::info("ClubPolicy::update - Admin bypass", ['user_id' => $user->id]);
            return true;
        }

        $isCreator = $club->created_by === $user->id;
        $isManager = $club->hasManager($user);
        $hasPermission = $user->hasPermissionTo('edit-own-club');
        $result = ($isCreator || $isManager) && $hasPermission;
        
        \Log::info("ClubPolicy::update - Check", [
            'user_id' => $user->id,
            'club_id' => $club->club_id,
            'created_by' => $club->created_by,
            'isCreator' => $isCreator,
            'isManager' => $isManager,
            'hasPermission' => $hasPermission,
            'result' => $result,
        ]);

        // User must be the creator or a manager of the club
        return $result;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Club $club): bool
    {
        // Admin can delete any club
        if ($user->hasRole('admin')) {
            return true;
        }

        // Only the creator can delete their own club
        return $club->created_by === $user->id && $user->hasPermissionTo('delete-own-club');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Club $club): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Club $club): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can approve the club.
     */
    public function approve(User $user, Club $club): bool
    {
        return $user->hasPermissionTo('accept-club');
    }
}
