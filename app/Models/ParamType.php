<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for param_type table.
 * Contains configuration for race types.
 */
class ParamType extends Model
{
    use HasFactory;

    protected $table = 'param_type';
    protected $primaryKey = 'typ_id';

    protected $fillable = [
        'typ_name',
    ];

    /**
     * Get the races that use this type.
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class, 'typ_id', 'typ_id');
    }
}
