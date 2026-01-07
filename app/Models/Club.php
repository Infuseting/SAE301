<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    use HasFactory;

    protected $table = 'clubs';
    protected $primaryKey = 'club_id';

    protected $fillable = [
        'club_name',
        'club_street',
        'club_city',
        'club_postal_code',
        'club_number',
        'adh_id',
        'adh_id_dirigeant',
    ];

    /**
     * Get the member (responsable) associated with the club.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'adh_id', 'adh_id');
    }

    /**
     * Get the member (dirigeant) associated with the club.
     */
    public function dirigeant(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'adh_id_dirigeant', 'adh_id');
    }

    /**
     * Get the raids for the club.
     */
    public function raids(): HasMany
    {
        return $this->hasMany(Raid::class, 'clu_id', 'club_id');
    }
}
