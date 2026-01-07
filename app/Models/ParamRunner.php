<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for param_runners table.
 * Contains configuration for minimum and maximum number of runners.
 */
class ParamRunner extends Model
{
    use HasFactory;

    protected $table = 'param_runners';
    protected $primaryKey = 'pac_id';

    protected $fillable = [
        'pac_nb_min',
        'pac_nb_max',
    ];

    /**
     * Get the races that use this param runner configuration.
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'pac_id', 'pac_id');
    }
}
