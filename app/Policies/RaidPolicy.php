<?php

namespace App\Policies;

use App\Models\Raid;
use App\Models\User;
use App\Models\Club;
use Illuminate\Auth\Access\Response;

/**
 * Policy for managing Raid access control
 */
class RaidPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Everyone can view raids (including guests)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Raid $raid): bool
    {
        // Everyone can view a specific raid (including guests)
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Only responsable-club can create raids for their club
     */
    public function create(User $user): bool
    {
        // Admin can always create raids
        if ($user->hasRole('admin')) {
            return true;
        }

        // Only responsable-club can create raids
        // They must have at least one approved club they are responsible for
        if ($user->hasRole('responsable-club')) {
            return $user->clubs()->where('is_approved', true)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create a raid for a specific club.
     */
    public function createForClub(User $user, Club $club): bool
    {
        // Admin can create raids for any club
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is responsable-club and member of this club
        if ($user->hasRole('responsable-club')) {
            return $user->clubs()->where('clubs.club_id', $club->club_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Raid $raid): bool
    {
        // Admin can update any raid
        if ($user->hasRole('admin')) {
            return true;
        }

        // Gestionnaire-raid can update raids they manage
        if ($user->hasRole('gestionnaire-raid') && $raid->adh_id) {
            $member = $user->member;
            if ($member && $member->adh_id === $raid->adh_id) {
                return true;
            }
        }

        // Responsable-club can update raids of their club
        // Responsable-club can update raids of their club
        if ($raid->clu_id) {
            $isRel = $user->hasRole('responsable-club') && $user->clubs()->where('clubs.club_id', $raid->clu_id)->exists();
            $isManager = $user->clubs()->where('clubs.club_id', $raid->clu_id)
                ->wherePivot('role', 'manager')
                ->wherePivot('status', 'approved')
                ->exists();

            file_put_contents('debug.log', "Policy Update: User " . $user->id . " Raid Club " . $raid->clu_id . " IsRel: " . ($isRel ? 1 : 0) . " IsManager: " . ($isManager ? 1 : 0) . "\n", FILE_APPEND);

            return $isRel || $isManager;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Raid $raid): bool
    {
        // Admin can delete any raid
        if ($user->hasRole('admin')) {
            return true;
        }

        // Responsable-club can delete raids of their club
        if ($raid->clu_id) {
            return $user->hasRole('responsable-club') && $user->clubs()->where('clubs.club_id', $raid->clu_id)->exists() ||
                $user->clubs()->where('clubs.club_id', $raid->clu_id)
                    ->wherePivot('role', 'manager')
                    ->wherePivot('status', 'approved')
                    ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Raid $raid): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Raid $raid): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign a gestionnaire-raid to the raid.
     */
    public function assignGestionnaireRaid(User $user, Raid $raid): bool
    {
        // Admin can assign anyone
        if ($user->hasRole('admin')) {
            return true;
        }

        // Responsable-club can assign gestionnaire-raid to their club's raids
        if ($user->hasRole('responsable-club') && $raid->clu_id) {
            return $user->clubs()->where('clubs.club_id', $raid->clu_id)->exists();
        }

        return false;
    }
}
