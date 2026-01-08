<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ClubInvitation model - Handles invitations to join clubs.
 * Supports both existing users and email-based invitations for new users.
 */
class ClubInvitation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'club_invitations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'club_id',
        'invited_by',
        'email',
        'token',
        'status',
        'role',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
            if (empty($invitation->expires_at)) {
                // Default: 30 days for email invites
                $invitation->expires_at = now()->addDays(30);
            }
        });
    }

    /**
     * Get the club this invitation is for.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'club_id', 'club_id');
    }

    /**
     * Get the user who sent the invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the invited user (if registered).
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    /**
     * Check if this is an email-based invitation.
     */
    public function isEmailInvitation(): bool
    {
        return (bool) $this->email;
    }

    /**
     * Mark the invitation as accepted.
     */
    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Mark the invitation as rejected.
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for invitations by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope for invitations for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('email', $user->email);
    }

    /**
     * Find invitation by token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }
}
