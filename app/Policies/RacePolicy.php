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
     */
    public function update(User $user, Race $race): bool
    {
        // Admin can update any race
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is the organizer of this race
        // TODO: Implement proper relationship between User and Member
        return $user->hasPermissionTo('edit-own-race');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Race $race): bool
    {
        // Admin can delete any race
        if ($user->hasRole('admin')) {
            return true;
        }

        // Only the organizer can delete their own race
        return $user->hasPermissionTo('delete-own-race');
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
