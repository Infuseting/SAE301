<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="PriceAgeCategory",
 *     title="PriceAgeCategory",
 *     description="Price and age category for races",
 *     @OA\Property(property="catp_id", type="integer", example=1),
 *     @OA\Property(property="catp_name", type="string", example="Adultes"),
 *     @OA\Property(property="catp_price", type="number", format="float", example=15.0),
 *     @OA\Property(property="age_min", type="integer", example=18),
 *     @OA\Property(property="age_max", type="integer", example=99),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class PriceAgeCategory extends Model
{
    use HasFactory;

    protected $table = 'price_age_category';
    protected $primaryKey = 'catp_id';

    protected $fillable = [
        'catp_name',
        'catp_price',
        'age_min',
        'age_max',
    ];

    /**
     * Get the races that use this category.
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(Race::class, 'has_category', 'catpd_id', 'race_id');
    }
}
