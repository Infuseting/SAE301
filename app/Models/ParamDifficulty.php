<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ParamDifficulty",
 *     title="ParamDifficulty",
 *     description="Difficulty parameters model for races",
 *     @OA\Property(property="dif_id", type="integer", example=1),
 *     @OA\Property(property="dif_level", type="string", example="medium"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ParamDifficulty extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'param_difficulty';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'dif_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dif_level',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dif_level' => 'string',
        ];
    }

    /**
     * Get the races that use this difficulty level.
     *
     * @return HasMany
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'dif_id', 'dif_id');
    }
}
