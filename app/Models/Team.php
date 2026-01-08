<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';
    protected $primaryKey = 'equ_id';

    protected $fillable = [
        'equ_name',
        'equ_image',
        'adh_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'adh_id', 'adh_id');
    }

    public function leaderboardResults(): HasMany
    {
        return $this->hasMany(LeaderboardTeam::class, 'equ_id', 'equ_id');
    }

    /**
     * Get participants via has_participate (using 'id' column).
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'has_participate', 'equ_id', 'id');
    }

    /**
     * Get users that belong to this team (using 'id_users' column).
     * Uses the has_participate pivot table.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'has_participate', 'equ_id', 'id_users');
    }

    /**
     * Get the team leader (user who created the team).
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adh_id', 'id');
    }
}
