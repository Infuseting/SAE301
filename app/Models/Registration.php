<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Registration Model
 * 
 * Represents a team's registration for a specific race.
 */
class Registration extends Model
{
    use HasFactory;

    protected $table = 'registration';
    protected $primaryKey = 'reg_id';

    protected $fillable = [
        'equ_id',
        'race_id',
        'pay_id',
        'doc_id',
        'reg_points',
        'reg_validated',
        'reg_dossard',
    ];

    protected $casts = [
        'reg_validated' => 'boolean',
        'reg_points' => 'float',
    ];

    /**
     * Get the team for this registration
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'equ_id', 'equ_id');
    }

    /**
     * Get the race for this registration
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
}
