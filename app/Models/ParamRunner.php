<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ParamRunner",
 *     title="ParamRunner",
 *     description="Runner parameters model for races",
 *     @OA\Property(property="pac_id", type="integer", example=1),
 *     @OA\Property(property="pac_nb_min", type="integer", example=1),
 *     @OA\Property(property="pac_nb_max", type="integer", example=100),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ParamRunner extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'param_runners';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'pac_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pac_nb_min',
        'pac_nb_max',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pac_nb_min' => 'integer',
            'pac_nb_max' => 'integer',
        ];
    }

    /**
     * Get the races that use these runner parameters.
     *
     * @return HasMany
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'pac_id', 'pac_id');
    }
}
