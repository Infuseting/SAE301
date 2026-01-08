<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Age Category model
 */
class AgeCategory extends Model
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
     * Get the param categorie ages for this age category.
     */
    public function paramCategorieAges(): HasMany
    {
        return $this->hasMany(ParamCategorieAge::class, 'age_categorie_id');
    }
}
