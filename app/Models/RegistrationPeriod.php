<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Get the raids for the registration period.
     */
    public function raids(): HasMany
    {
        return $this->hasMany(Raid::class, 'ins_id', 'ins_id');
    }
}
