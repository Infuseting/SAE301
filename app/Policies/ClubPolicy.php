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
     */
    public function create(User $user): bool
    {
        // Only users with responsable-club role can create clubs
        return $user->hasPermissionTo('create-club') && $user->hasRole('responsable-club');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Club $club): bool
    {
        // Admin can update any club
        if ($user->hasRole('admin')) {
            return true;
        }

        // User must be the creator or a manager of the club
        return ($club->created_by === $user->id || $club->hasManager($user)) 
            && $user->hasPermissionTo('edit-own-club');
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
