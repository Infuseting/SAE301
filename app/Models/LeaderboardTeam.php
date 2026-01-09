<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="LeaderboardTeam",
 *     title="LeaderboardTeam",
 *     description="Team leaderboard result with average scores and points",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="equ_id", type="integer", example=1),
 *     @OA\Property(property="race_id", type="integer", example=1),
 *     @OA\Property(property="average_temps", type="number", format="float", example=3600.50),
 *     @OA\Property(property="average_malus", type="number", format="float", example=60.00),
 *     @OA\Property(property="average_temps_final", type="number", format="float", example=3660.50),
 *     @OA\Property(property="member_count", type="integer", example=3),
 *     @OA\Property(property="points", type="integer", example=100, description="Points earned based on ranking"),
 *     @OA\Property(property="age_category_id", type="integer", example=1, description="Age category ID for competitive races"),
 *     @OA\Property(property="puce", type="string", example="7000586", description="Chip/puce number"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class LeaderboardTeam extends Model
{
    use HasFactory;

    protected $table = 'leaderboard_teams';

    protected $fillable = [
        'equ_id',
        'race_id',
        'average_temps',
        'average_malus',
        'average_temps_final',
        'member_count',
        'points',
        'age_category_id',
        'puce',
    ];

    protected $casts = [
        'average_temps' => 'decimal:2',
        'average_malus' => 'decimal:2',
        'average_temps_final' => 'decimal:2',
        'member_count' => 'integer',
        'points' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'equ_id', 'equ_id');
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
    }

    /**
     * Get the age category for this leaderboard entry.
     */
    public function ageCategory(): BelongsTo
    {
        return $this->belongsTo(AgeCategory::class, 'age_category_id');
    }

    public function getFormattedAverageTempsAttribute(): string
    {
        return $this->formatTime($this->average_temps);
    }

    public function getFormattedAverageMalusAttribute(): string
    {
        return $this->formatTime($this->average_malus);
    }

    public function getFormattedAverageTempsFinalAttribute(): string
    {
        return $this->formatTime($this->average_temps_final);
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
