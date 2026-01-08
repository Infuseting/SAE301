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
 *     @OA\Property(property="status", type="string", example="classé", description="Team status: classé, abandon, disqualifié, hors_classement"),
 *     @OA\Property(property="category", type="string", example="Masculin", description="Team category: Masculin, Féminin, Mixte"),
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
        'status',
        'category',
        'puce',
    ];

    protected $casts = [
        'average_temps' => 'decimal:2',
        'average_malus' => 'decimal:2',
        'average_temps_final' => 'decimal:2',
        'member_count' => 'integer',
        'points' => 'integer',
    ];

    /**
     * Valid status values for teams.
     */
    public const STATUS_CLASSIFIED = 'classé';
    public const STATUS_ABANDONED = 'abandon';
    public const STATUS_DISQUALIFIED = 'disqualifié';
    public const STATUS_OUT_OF_RANKING = 'hors_classement';

    /**
     * Valid category values for teams.
     */
    public const CATEGORY_MALE = 'Masculin';
    public const CATEGORY_FEMALE = 'Féminin';
    public const CATEGORY_MIXED = 'Mixte';

    /**
     * Check if team is classified (eligible for ranking).
     */
    public function isClassified(): bool
    {
        return $this->status === self::STATUS_CLASSIFIED;
    }

    /**
     * Check if team should receive 0 points (abandoned, disqualified, or out of ranking).
     */
    public function hasZeroPoints(): bool
    {
        return in_array($this->status, [
            self::STATUS_ABANDONED,
            self::STATUS_DISQUALIFIED,
            self::STATUS_OUT_OF_RANKING,
        ]);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'equ_id', 'equ_id');
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class, 'race_id', 'race_id');
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
