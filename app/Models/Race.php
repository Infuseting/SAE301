<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Spatie\ActivityLog\Traits\LogsActivity;
// use Spatie\ActivityLog\LogOptions;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Race",
 *     title="Race",
 *     description="Orienteering race model",
 *     @OA\Property(property="race_id", type="integer", example=1),
 *     @OA\Property(property="race_name", type="string", example="La Boussole de la Forêt"),
 *     @OA\Property(property="race_date_start", type="string", format="date-time", example="2026-10-12T09:00:00Z"),
 *     @OA\Property(property="race_date_end", type="string", format="date-time", example="2026-10-12T17:00:00Z"),
 *     @OA\Property(property="race_reduction", type="number", format="float", nullable=true, example=5.0),
 *     @OA\Property(property="race_meal_price", type="number", format="float", nullable=true, example=8.0),
 *     @OA\Property(property="race_duration_minutes", type="number", format="float", nullable=true, example=150),
 *     @OA\Property(property="raid_id", type="integer", example=1),
 *     @OA\Property(property="adh_id", type="integer", example=1),
 *     @OA\Property(property="pac_id", type="integer", example=1),
 *     @OA\Property(property="pae_id", type="integer", example=1),
 *     @OA\Property(property="dif_id", type="integer", example=2),
 *     @OA\Property(property="typ_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Race extends Model
{
    use HasFactory;
    // use LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'races';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'race_id';

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'race_id';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'race_name',
        'race_description',
        'race_date_start',
        'race_date_end',
        'race_reduction',
        'race_meal_price',
        'race_duration_minutes',
        'image_url',
        'raid_id',
        'adh_id',
        'pac_id',
        'pae_id',
        'typ_id',
        'race_difficulty',
        'price_major',
        'price_minor',
        'price_adherent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'race_date_start' => 'datetime',
        'race_date_end' => 'datetime',
        'race_reduction' => 'float',
        'race_meal_price' => 'float',
        'race_duration_minutes' => 'float',
        'price_major' => 'float',
        'price_minor' => 'float',
        'price_adherent' => 'float',
    ];

    /**
     * Get the activity log options for this model.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['race_name', 'race_date_start', 'race_date_end', 'race_reduction', 'race_meal_price', 'race_duration_minutes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the raid (event) this race belongs to.
     *
     * @return BelongsTo
     */
    public function raid(): BelongsTo
    {
        return $this->belongsTo(Raid::class, 'raid_id', 'raid_id');
    }

    /**
     * Get the organizer (member) who created this race.
     *
     * @return BelongsTo
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'adh_id', 'adh_id');
    }

    /**
     * Get the runner parameters for this race.
     *
     * @return BelongsTo
     */
    public function runnerParams(): BelongsTo
    {
        return $this->belongsTo(ParamRunner::class, 'pac_id', 'pac_id');
    }

    /**
     * Get the team parameters for this race.
     *
     * @return BelongsTo
     */
    public function teamParams(): BelongsTo
    {
        return $this->belongsTo(ParamTeam::class, 'pae_id', 'pae_id');
    }

    /**
     * Get the race type of this race.
     *
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ParamType::class, 'typ_id', 'typ_id');
    }

    /**
     * Get the age categories for this race.
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PriceAgeCategory::class, 'has_category', 'race_id', 'catpd_id', 'race_id', 'catp_id');
    }

    /**
     * Get the participants registered for this race.
     *
     * @return BelongsToMany
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'has_participate', 'race_id', 'adh_id', 'race_id', 'adh_id')
            ->withPivot('reg_id', 'par_time')
            ->withTimestamps();
    }

    /**
     * Get the teams participating in this race.
     *
     * @return BelongsToMany
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'races_has_teams', 'race_id', 'eq_id', 'race_id', 'eq_id');
    }

    /**
     * Get the number of registered participants.
     *
     * @return int
     */
    public function getRegisteredCountAttribute(): int
    {
        return $this->participants()->count();
    }

    /**
     * Check if a member is registered for this race.
     *
     * @param Member|int $member
     * @return bool
     */
    public function isMemberRegistered($member): bool
    {
        $memberId = $member instanceof Member ? $member->adh_id : $member;
        return $this->participants()->where('adh_id', $memberId)->exists();
    }

    /**
     * Check if the race is upcoming (in the future).
     *
     * @return bool
     */
    public function isUpcoming(): bool
    {
        return $this->race_date_start->isFuture();
    }

    /**
     * Check if the race is ongoing (between start and end dates).
     *
     * @return bool
     */
    public function isOngoing(): bool
    {
        $now = now();
        return $this->race_date_start <= $now && $now <= $this->race_date_end;
    }

    /**
     * Check if the race is completed (past end date).
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->race_date_end->isPast();
    }

    /**
     * Get the race status (upcoming, ongoing, completed).
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if ($this->isOngoing()) {
            return 'ongoing';
        } elseif ($this->isCompleted()) {
            return 'completed';
        }
        return 'upcoming';
    }

    /**
     * Check if the race is currently open for registration (proxied from raid).
     */
    public function isOpen(): bool
    {
        return $this->raid ? $this->raid->isOpen() : false;
    }

    /**
     * Check if registration is in the future.
     */
    public function isRegistrationUpcoming(): bool
    {
        return $this->raid ? $this->raid->isUpcoming() : true;
    }

    /**
     * Get the individual leaderboard results for this race.
     *
     * @return HasMany
     */
    public function leaderboardUsers(): HasMany
    {
        return $this->hasMany(LeaderboardUser::class, 'race_id', 'race_id');
    }

    /**
     * Get the team leaderboard results for this race.
     *
     * @return HasMany
     */
    public function leaderboardTeams(): HasMany
    {
        return $this->hasMany(LeaderboardTeam::class, 'race_id', 'race_id');
    }

    public function times()
    {
        // Une Race possède plusieurs entrées dans la table 'time'
        // 'race_id' est la clé étrangère dans la table 'time'
        return $this->hasMany(Time::class, 'race_id', 'race_id');
    }
}
