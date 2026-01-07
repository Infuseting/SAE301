<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Club model representing orienteering clubs
 *
 * @OA\Schema(
 *     schema="Club",
 *     title="Club",
 *     description="Club model",
 *     @OA\Property(property="club_id", type="integer", description="Club ID"),
 *     @OA\Property(property="club_name", type="string", description="Name of the club"),
 *     @OA\Property(property="club_street", type="string", description="Street address"),
 *     @OA\Property(property="club_city", type="string", description="City"),
 *     @OA\Property(property="club_postal_code", type="string", description="Postal code"),
 *     @OA\Property(property="is_approved", type="boolean", description="Approval status"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Update timestamp")
 * )
 *
 * @property int $club_id
 * @property string $club_name
 * @property string $club_street
 * @property string $club_city
 * @property string $club_postal_code
 * @property string|null $ffso_id
 * @property string|null $description
 * @property bool $is_approved
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property int $created_by
 */
class Club extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'club_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'club_name',
        'club_street',
        'club_city',
        'club_postal_code',
        'ffso_id',
        'description',
        'club_image',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['club_name', 'club_street', 'club_city', 'club_postal_code', 'ffso_id', 'description', 'is_approved'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user who created the club.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the club.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all members of the club (including managers).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_user', 'club_id', 'user_id')
            ->withPivot(['role', 'status'])
            ->withTimestamps()
            ->wherePivot('status', 'approved');
    }

    /**
     * Get only the managers of the club.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_user', 'club_id', 'user_id')
            ->withPivot(['role', 'status'])
            ->withTimestamps()
            ->wherePivot('role', 'manager')
            ->wherePivot('status', 'approved');
    }

    /**
     * Get pending join requests for the club.
     */
    public function pendingRequests(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_user', 'club_id', 'user_id')
            ->withPivot(['role', 'status'])
            ->withTimestamps()
            ->wherePivot('status', 'pending');
    }

    /**
     * Get all members including pending and approved.
     */
    public function allMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_user', 'club_id', 'user_id')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include approved clubs.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include pending clubs.
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Check if a user is a member of this club.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is a manager of this club.
     */
    public function hasManager(User $user): bool
    {
        return $this->managers()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the raids belonging to this club.
     */
    public function raids()
    {
        return $this->hasMany(Raid::class, 'clu_id', 'club_id');
    }
}
