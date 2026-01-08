<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ParamTeam",
 *     title="ParamTeam",
 *     description="Team parameters model for races",
 *     @OA\Property(property="pae_id", type="integer", example=1),
 *     @OA\Property(property="pae_nb_min", type="integer", example=2),
 *     @OA\Property(property="pae_nb_max", type="integer", example=5),
 *     @OA\Property(property="pae_team_count_max", type="integer", example=30),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ParamTeam extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'param_teams';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'pae_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pae_nb_min',
        'pae_nb_max',
        'pae_team_count_min',
        'pae_team_count_max',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pae_nb_min' => 'integer',
            'pae_nb_max' => 'integer',
            'pae_team_count_max' => 'integer',
        ];
    }

    /**
     * Get the races that use these team parameters.
     *
     * @return HasMany
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'pae_id', 'pae_id');
    }
}
