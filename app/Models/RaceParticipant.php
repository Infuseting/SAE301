<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RaceParticipant Model
 * 
 * Represents a runner participating in a specific race registration.
 * Each runner can have different PPS information for each race they participate in.
 */
class RaceParticipant extends Model
{
    use HasFactory;

    protected $table = 'race_participants';
    protected $primaryKey = 'rpa_id';

    protected $fillable = [
        'reg_id',
        'user_id',
        'pps_number',
        'pps_expiry',
        'pps_status',
        'pps_verified_at',
        'runner_time',
        'bib_number',
    ];

    protected $casts = [
        'pps_expiry' => 'date',
        'pps_verified_at' => 'datetime',
        'runner_time' => 'datetime',
    ];

    /**
     * Get the registration this participant belongs to
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'reg_id', 'reg_id');
    }

    /**
     * Get the user (runner) for this participation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Check if this participant has a valid PPS
     */
    public function hasValidPps(): bool
    {
        return $this->pps_number &&
            $this->pps_expiry &&
            $this->pps_expiry->isFuture() &&
            $this->pps_status === 'verified' &&
            !str_starts_with($this->pps_number, 'PENDING-');
    }

    /**
     * Check if this participant has a valid licence
     */
    public function hasValidLicence(): bool
    {
        $user = $this->user;
        if (!$user || !$user->member) {
            return false;
        }

        $licence = $user->member->adh_license;
        $licenceExpiry = $user->member->adh_end_validity;

        return $licence && $licenceExpiry && $licenceExpiry > now();
    }

    /**
     * Check if this participant has valid credentials (licence OR PPS)
     */
    public function hasValidCredentials(): bool
    {
        return $this->hasValidLicence() || $this->hasValidPps();
    }
}
