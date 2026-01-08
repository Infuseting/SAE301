<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RaceRegistration Model
 * 
 * Represents a registration for a race. Supports both:
 * - Permanent teams (via equ_id referencing teams)
 * - Temporary teams (members stored in temporary_team_data JSON)
 */
class RaceRegistration extends Model
{
    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'reg_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'race_id',
        'user_id',           // User who registered
        'equ_id',            // Permanent team (nullable)
        'is_team_leader',
        'is_temporary_team',
        'temporary_team_data',
        'is_creator_participating',
        'status',
        'amount_paid',
        'confirmed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_team_leader' => 'boolean',
        'is_temporary_team' => 'boolean',
        'temporary_team_data' => 'array',
        'is_creator_participating' => 'boolean',
        'amount_paid' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Get the race this registration is for.
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }

    /**
     * Get the permanent team (if using one).
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'equ_id', 'equ_id');
    }

    /**
     * Get the user who created this registration.
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for registeredBy() for consistency.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get invitations sent for this registration (temporary teams).
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class, 'registration_id', 'reg_id');
    }

    // -------------------------------------------------------------------------
    // Status Methods
    // -------------------------------------------------------------------------

    /**
     * Check if registration is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if registration is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if registration is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if this registration uses a temporary team.
     */
    public function usesTemporaryTeam(): bool
    {
        return $this->is_temporary_team && !$this->equ_id;
    }

    // -------------------------------------------------------------------------
    // Team Member Methods
    // -------------------------------------------------------------------------

    /**
     * Get all team members (works for both permanent and temporary teams).
     * Returns array of user data with status.
     */
    public function getTeamMembers(): array
    {
        if ($this->usesTemporaryTeam()) {
            return $this->temporary_team_data ?? [];
        }

        // For permanent teams, get members from the team
        if ($this->team && $this->team->users) {
            $members = $this->team->users->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'status' => 'confirmed',
                ];
            })->toArray();

            // Always remove the registration creator from members list
            // They are displayed separately as team leader
            if ($this->user_id) {
                $members = array_filter($members, function ($member) {
                    return $member['user_id'] !== $this->user_id;
                });
                // Reindex array
                $members = array_values($members);
            }

            return $members;
        }

        return [];
    }

    /**
     * Update temporary team member status.
     */
    public function updateMemberStatus(int $userId, string $status): bool
    {
        if (!$this->usesTemporaryTeam()) {
            return false;
        }

        $members = $this->temporary_team_data ?? [];
        $updated = false;

        foreach ($members as &$member) {
            if (isset($member['user_id']) && $member['user_id'] === $userId) {
                $member['status'] = $status;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->temporary_team_data = $members;
            $this->save();
        }

        return $updated;
    }

    /**
     * Check if all temporary team members are confirmed.
     */
    public function allMembersConfirmed(): bool
    {
        if (!$this->usesTemporaryTeam()) {
            return true;
        }

        $members = $this->temporary_team_data ?? [];

        foreach ($members as $member) {
            if (($member['status'] ?? '') !== 'accepted') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if team meets race requirements.
     */
    public function isTeamComplete(): bool
    {
        if (!$this->race) {
            return false;
        }

        $teamParams = $this->race->teamParams;
        if (!$teamParams) {
            return true;
        }

        $memberCount = count($this->getTeamMembers());
        $minMembers = $teamParams->pae_nb_min ?? 1;

        return $memberCount >= $minMembers;
    }

    /**
     * Check if registration can be edited.
     */
    public function canEdit(): bool
    {
        // Can edit if:
        // - Uses temporary team
        // - Is team leader
        // - Status is pending or confirmed
        // - Race hasn't started yet
        return $this->is_temporary_team
            && $this->is_team_leader
            && in_array($this->status, ['pending', 'confirmed'])
            && $this->race
            && $this->race->race_date_start
            && now()->isBefore($this->race->race_date_start);
    }

    /**
     * Get count of pending invitations.
     */
    public function getPendingInvitationsCount(): int
    {
        if (!$this->usesTemporaryTeam()) {
            return 0;
        }

        $members = $this->temporary_team_data ?? [];
        $pendingCount = 0;

        foreach ($members as $member) {
            if (($member['status'] ?? '') === 'pending') {
                $pendingCount++;
            }
        }

        return $pendingCount;
    }
}
