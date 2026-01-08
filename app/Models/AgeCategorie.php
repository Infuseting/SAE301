<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgeCategorie extends Model
{
    /**
     * Get all param categories associated with this age category.
     */
    public function paramCategories(): HasMany
    {
        return $this->hasMany(ParamCategorie::class, 'age_categorie_id');
    }
}
