<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    use HasFactory;

    protected $table = 'races';
    protected $primaryKey = 'race_id';

    protected $fillable = [
        'race_name',
        'race_date_start',
        'race_date_end',
        'race_reduction',
        'race_meal_price',
        'race_duration_minutes',
        'raid_id',
        'cla_id',
        'adh_id',
        'pac_id',
        'pae_id',
        'dif_id',
        'typ_id',
    ];

    protected $casts = [
        'race_date_start' => 'datetime',
        'race_date_end' => 'datetime',
        'race_reduction' => 'decimal:2',
        'race_meal_price' => 'decimal:2',
        'race_duration_minutes' => 'decimal:2',
    ];

    public function raid(): BelongsTo
    {
        return $this->belongsTo(raid::class, 'raid_id', 'raid_id');
    }

    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(leaderboard::class, 'cla_id', 'cla_id');
    }

    public function leaderboardResults(): HasMany
    {
        return $this->hasMany(leaderboardResult::class, 'race_id', 'race_id');
    }

    public function leaderboardTeams(): HasMany
    {
        return $this->hasMany(leaderboardTeam::class, 'race_id', 'race_id');
    }
}
