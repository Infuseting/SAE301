<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Raid",
 *     title="Raid",
 *     description="Raid model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Mountain Adventure Raid"),
 *     @OA\Property(property="event_start_date", type="string", format="date-time", example="2026-06-15T08:00:00Z"),
 *     @OA\Property(property="event_end_date", type="string", format="date-time", example="2026-06-17T18:00:00Z"),
 *     @OA\Property(property="registration_start_date", type="string", format="date-time", example="2026-03-01T00:00:00Z"),
 *     @OA\Property(property="registration_end_date", type="string", format="date-time", example="2026-06-10T23:59:59Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Raid extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'event_start_date',
        'event_end_date',
        'registration_start_date',
        'registration_end_date',
        'adherent_id',
        'club_id',
        'periode_inscription_id',
        'contact',
        'website_url',
        'image',
        'address',
        'postal_code',
        'number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_start_date' => 'datetime',
            'event_end_date' => 'datetime',
            'registration_start_date' => 'datetime',
            'registration_end_date' => 'datetime',
        ];
    }
}
