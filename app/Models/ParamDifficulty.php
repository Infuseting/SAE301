<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for param_difficulty table.
 * Contains configuration for race difficulty levels.
 */
class ParamDifficulty extends Model
{
    use HasFactory;

    protected $table = 'param_difficulty';
    protected $primaryKey = 'dif_id';

    protected $fillable = [
        'dif_level',
    ];

    /**
     * Get the races that use this difficulty level.
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'dif_id', 'dif_id');
    }
}
