<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="LeaderboardUser",
 *     title="LeaderboardUser",
 *     description="Individual runner leaderboard result",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="race_id", type="integer", example=1),
 *     @OA\Property(property="temps", type="number", format="float", example=3600.50),
 *     @OA\Property(property="malus", type="number", format="float", example=60.00),
 *     @OA\Property(property="points", type="integer", example=100, description="Points earned based on ranking"),
 *     @OA\Property(property="temps_final", type="number", format="float", example=3660.50),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class LeaderboardUser extends Model
{
    use HasFactory;

    protected $table = 'leaderboard_users';

    protected $fillable = [
        'user_id',
        'race_id',
        'temps',
        'malus',
        'points',
    ];

    protected $casts = [
        'temps' => 'decimal:2',
        'malus' => 'decimal:2',
        'temps_final' => 'decimal:2',
        'points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }

    public function getFormattedTempsAttribute(): string
    {
        return $this->formatTime($this->temps);
    }

    public function getFormattedMalusAttribute(): string
    {
        return $this->formatTime($this->malus);
    }

    public function getFormattedTempsFinalAttribute(): string
    {
        return $this->formatTime($this->temps_final);
    }

    private function formatTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(fmod($seconds, 3600) / 60);
        $secs = fmod($seconds, 60);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%05.2f', $hours, $minutes, $secs);
        }

        return sprintf('%02d:%05.2f', $minutes, $secs);
    }
}
