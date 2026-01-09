<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Observers\RegistrationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 
 * Represents a team registration for a race.
 * Automatically generates QR codes when validated.
 */
#[ObservedBy([RegistrationObserver::class])]
class Registration extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'registration';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'reg_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'equ_id',
        'race_id',
        'pay_id',
        'doc_id',
        'reg_points',
        'reg_validated',
        'reg_dossard',
        'qr_code_path',
        'is_present',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'reg_validated' => 'boolean',
        'is_present' => 'boolean',
        'reg_points' => 'float',
    ];

    /**
     * Get the team that owns the registration.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'equ_id', 'equ_id');
    }

    /**
     * Get the race that owns the registration.
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }

    /**
     * Get all participants (runners) for this registration
     */
    public function participants(): HasMany
    {
        return $this->hasMany(RaceParticipant::class, 'reg_id', 'reg_id');
    }

    /**
     * Get the payment for this registration
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'pay_id', 'pai_id');
    }

    /**
     * Get the secured URL to the QR code image
     * This route requires authentication and team membership verification
     * 
     * @return string|null Secured route URL to download QR code
     */
    public function getQrCodeUrlAttribute(): ?string
    {
        if (empty($this->qr_code_path) || !$this->team) {
            return null;
        }

        // Return a secured route instead of direct asset URL
        return route('teams.registration.qr-code', [
            'team' => $this->team->equ_id,
            'registration' => $this->reg_id
        ]);
    }

    /**
     * Get the legacy public asset URL for backward compatibility
     * Only use this if you absolutely need direct access (deprecated)
     * 
     * @return string|null Public asset URL
     * @deprecated Use getQrCodeUrlAttribute() instead for security
     */
    public function getPublicQrCodeUrlAttribute(): ?string
    {
        if (empty($this->qr_code_path)) {
            return null;
        }

        return asset('storage/' . $this->qr_code_path);
    }
}
