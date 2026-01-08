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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'raids';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'raid_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'raid_name',
        'raid_description',
        'adh_id',
        'clu_id',
        'ins_id',
        'raid_date_start',
        'raid_date_end',
        'raid_contact',
        'raid_site_url',
        'raid_image',
        'raid_street',
        'raid_city',
        'raid_postal_code',
        'raid_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raid_date_start' => 'datetime',
            'raid_date_end' => 'datetime',
        ];
    }

    /**
     * Get the route key name for model binding.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'raid_id';
    }

    /**
     * Get the club this raid belongs to.
     */
    public function club()
    {
        return $this->belongsTo(Club::class, 'clu_id', 'club_id');
    }

    /**
     * Get the races for this raid.
     */
    public function races()
    {
        return $this->hasMany(Race::class, 'raid_id', 'raid_id');
    }

    /**
     * Get the registration period for this raid.
     */
    public function registrationPeriod()
    {
        return $this->belongsTo(RegistrationPeriod::class, 'ins_id', 'ins_id');
    }

    /**
     * Check if the raid is currently open for registration.
     */
    public function isOpen(): bool
    {
        $period = $this->registrationPeriod;
        if (!$period) return false;
        
        $now = now();
        return $now >= $period->ins_start_date && $now <= $period->ins_end_date;
    }

    /**
     * Check if the raid registration is in the future.
     */
    public function isUpcoming(): bool
    {
        $period = $this->registrationPeriod;
        if (!$period) return true;
        
        return now() < $period->ins_start_date;
    }

    /**
     * Check if the raid (event or registration) is finished.
     */
    public function isFinished(): bool
    {
        return now() > $this->raid_date_end;
    }
    
}
