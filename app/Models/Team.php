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

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'has_participate', 'equ_id', 'id');
    }
}
