<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raid extends Model
{
    use HasFactory;

    protected $table = 'raids';
    protected $primaryKey = 'raid_id';

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

    protected $casts = [
        'raid_date_start' => 'datetime',
        'raid_date_end' => 'datetime',
    ];

    /**
     * Get the member associated with the raid.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'adh_id', 'adh_id');
    }

    /**
     * Get the club associated with the raid.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'clu_id', 'club_id');
    }

    /**
     * Get the registration period associated with the raid.
     */
    public function registrationPeriod(): BelongsTo
    {
        return $this->belongsTo(RegistrationPeriod::class, 'ins_id', 'ins_id');
    }

    /**
     * Get the races for the raid.
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'raid_id', 'raid_id');
    }
}
