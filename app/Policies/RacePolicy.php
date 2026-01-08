<?php

namespace App\Policies;

use App\Models\Race;
use App\Models\User;
use App\Models\Raid;
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
    public function create(User $user, ?Raid $raid = null): bool
    {
        // Admin can always create races
        if ($user->hasRole('admin')) {
            return true;
        }

        // If a raid is provided, check if the user has authority over it
        if ($raid) {
            // Gestionnaire-raid can create races for raids they manage
            if ($user->hasRole('gestionnaire-raid') && $raid->adh_id) {
                $member = $user->member;
                if ($member && $member->adh_id === $raid->adh_id) {
                    return true;
                }
            }

            // Responsable-club can ONLY create races for raids of their club IF they are the raid responsible
            if ($raid->clu_id) {
                $isClubManager = $user->hasRole('responsable-club') || 
                               $user->clubs()->where('clubs.club_id', $raid->clu_id)
                                    ->wherePivot('role', 'manager')
                                    ->wherePivot('status', 'approved')
                                    ->exists();

                if ($isClubManager && $raid->adh_id) {
                    $member = $user->member;
                    if ($member && $member->adh_id === $raid->adh_id) {
                        return true;
                    }
                }
            }
        }
        
        // Default check: Only users with responsable-course role and permission
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
     * Any authenticated user can attempt to register - credential validation
     * is done at the team level in the controller.
     */
    public function register(User $user, Race $race): bool
    {
        // Any authenticated user can attempt to register
        // The controller will validate that all team members have valid credentials
        return true;
    }
}
