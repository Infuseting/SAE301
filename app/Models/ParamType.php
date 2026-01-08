<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ParamType",
 *     title="ParamType",
 *     description="Type parameters model for races",
 *     @OA\Property(property="typ_id", type="integer", example=1),
 *     @OA\Property(property="typ_name", type="string", example="medium"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ParamType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'param_type';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'typ_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'typ_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'typ_name' => 'string',
        ];
    }

    /**
     * Get the races that use this type.
     *
     * @return HasMany
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'typ_id', 'typ_id');
    }
}
