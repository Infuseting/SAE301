<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AgeCategorie extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'age_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'age_min',
        'age_max',
    ];

    /**
     * Get all param categories associated with this age category.
     */
    public function paramCategories(): HasMany
    {
        return $this->hasMany(ParamCategorie::class, 'age_categorie_id');
    }

    /**
     * Get the races that have this age category.
     *
     * @return BelongsToMany
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(
            Race::class,
            'param_categorie_age',
            'age_categorie_id',
            'race_id',
            'id',
            'race_id'
        );
    }
}
