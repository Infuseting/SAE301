<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Observers\RegistrationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * Registration Model
 * 
 * Represents a team registration for a race.
 * Automatically generates QR codes when validated.
 */
#[ObservedBy([RegistrationObserver::class])]
class Registration extends Model
{
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
     * Get the full URL to the QR code image
     */
    public function getQrCodeUrlAttribute(): ?string
    {
        if (empty($this->qr_code_path)) {
            return null;
        }

        return asset('storage/' . $this->qr_code_path);
    }
}
