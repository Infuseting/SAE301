<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParamCategorieAge extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'param_categorie_age';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'race_id',
        'age_categorie_id',
    ];

    /**
     * Get the race associated with this parameter.
     *
     * @return BelongsTo
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }

    /**
     * Get the age category associated with this parameter.
     *
     * @return BelongsTo
     */
    public function ageCategorie(): BelongsTo
    {
        return $this->belongsTo(AgeCategorie::class, 'age_categorie_id');
    }
}
