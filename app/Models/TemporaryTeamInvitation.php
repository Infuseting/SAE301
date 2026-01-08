<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * TemporaryTeamInvitation model - Handles invitations for temporary race teams.
 * Supports email-based invitations with token authentication.
 */
class TemporaryTeamInvitation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'temporary_team_invitations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'registration_id',
        'inviter_id',
        'email',
        'token',
        'status',
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
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Get the registration this invitation belongs to.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(RaceRegistration::class, 'registration_id', 'reg_id');
    }

    /**
     * Get the user who sent the invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Check if invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
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
     * Mark the invitation as expired.
     */
    public function expire(): void
    {
        $this->update(['status' => 'expired']);
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
     * Scope for expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<=', now())
                ->orWhere('status', 'expired');
        });
    }

    /**
     * Scope for invitations by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Find invitation by token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }
}
