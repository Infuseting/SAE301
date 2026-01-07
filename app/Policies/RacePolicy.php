<?php

namespace App\Policies;

use App\Models\Race;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy for managing Race access control
 */
class RacePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Everyone can view races (including guests)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Race $race): bool
    {
        // Everyone can view a specific race (including guests)
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin can always create races
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Only users with responsable-course role can create races
        return $user->hasRole('responsable-course') && $user->hasPermissionTo('create-race');
    }

    /**
     * Determine whether the user can update the model.
     * Only the race organizer (adh_id matches user's member) or admin can update.
     */
    public function update(User $user, Race $race): bool
    {
        // Admin can update any race
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user has the edit-own-race permission
        if (!$user->hasPermissionTo('edit-own-race')) {
            return false;
        }

        // Check if user is the organizer of this race (adh_id matches)
        return $user->adh_id !== null && $user->adh_id === $race->adh_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Only the race organizer (adh_id matches user's member) or admin can delete.
     */
    public function delete(User $user, Race $race): bool
    {
        // Admin can delete any race
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user has the delete-own-race permission
        if (!$user->hasPermissionTo('delete-own-race')) {
            return false;
        }

        // Only the organizer can delete their own race (adh_id matches)
        return $user->adh_id !== null && $user->adh_id === $race->adh_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Race $race): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Race $race): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can register for a race.
     */
    public function register(User $user, Race $race): bool
    {
        return $user->hasPermissionTo('register-to-race');
    }
}
