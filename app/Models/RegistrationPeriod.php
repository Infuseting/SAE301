<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RegistrationPeriod",
 *     title="Registration Period",
 *     description="Registration period for a raid event",
 *     @OA\Property(property="ins_id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="ins_start_date", type="string", format="date-time", example="2026-01-01T08:00:00Z"),
 *     @OA\Property(property="ins_end_date", type="string", format="date-time", example="2026-05-31T23:59:59Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class RegistrationPeriod extends Model
{
    use HasFactory;

    protected $table = 'registration_period';
    protected $primaryKey = 'ins_id';

    protected $fillable = [
        'ins_start_date',
        'ins_end_date',
    ];

    protected $casts = [
        'ins_start_date' => 'datetime',
        'ins_end_date' => 'datetime',
    ];

    /**
     * Get the raids associated with this registration period.
     */
    public function raids()
    {
        return $this->hasMany(Raid::class, 'ins_id', 'ins_id');
    }
}
