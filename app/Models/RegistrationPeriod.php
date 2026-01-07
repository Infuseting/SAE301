<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * RegistrationPeriod Model
 * Represents the registration period for a raid event
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
