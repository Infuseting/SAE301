<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Time model represents a participant's time for a race.
 * 
 * @property int $user_id
 * @property int $race_id
 * @property float $time_hours
 * @property float $time_minutes
 * @property float $time_seconds
 * @property float $time_total_seconds
 * @property int $time_rank
 * @property int $time_rank_start
 */
class Time extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'time';

    /**
     * The primary key(s) associated with the table.
     *
     * @var array|string
     */
    protected $primaryKey = ['user_id', 'race_id'];

    /**
     * Indicates if the model has composite primary key.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'race_id',
        'time_hours',
        'time_minutes',
        'time_seconds',
        'time_total_seconds',
        'time_rank',
        'time_rank_start',
    ];

    /**
     * Get the user associated with this time record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the race associated with this time record.
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }
}
