<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for param_teams table.
 * Contains configuration for minimum and maximum team members and team count.
 */
class ParamTeam extends Model
{
    use HasFactory;

    protected $table = 'param_teams';
    protected $primaryKey = 'pae_id';

    protected $fillable = [
        'pae_nb_min',
        'pae_nb_max',
        'pae_team_count_max',
    ];

    /**
     * Get the races that use this param team configuration.
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'pae_id', 'pae_id');
    }
}
