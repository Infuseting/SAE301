<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use OpenApi\Annotations as OA;
use App\Models\Member;
use App\Models\MedicalDoc;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="password_is_set", type="boolean", example=true, description="True if the user has a local password set"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *          property="connected_accounts",
 *          type="array",
 *          @OA\Items(ref="#/components/schemas/ConnectedAccount"),
 *          description="List of connected social accounts (if loaded)"
 *     )
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'password_is_set',
        'doc_id',
        'adh_id',
        'birth_date',
        'address',
        'phone',
        'is_public',
        'description',
        'profile_photo_path',
    ];

    /**
     * Get the user's member details.
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'adh_id', 'adh_id');
    }

    /**
     * Get the user's medical document details.
     */
    public function medicalDoc()
    {
        return $this->belongsTo(MedicalDoc::class, 'doc_id', 'doc_id');
    }

    /**
     * Get the teams that this user belongs to.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'has_participate', 'id_users', 'equ_id');
    }

    /**
     * Get the user's full name.
     */
    protected function name(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn() => "{$this->first_name} {$this->last_name}",
        );
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'has_completed_profile',
        'profile_photo_url',
        'name',
        'license_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_is_set' => 'boolean',
            'birth_date' => 'date',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get the URL to the user's profile photo.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function profilePhotoUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function () {
            return $this->profile_photo_path
                ? \Illuminate\Support\Facades\Storage::url($this->profile_photo_path)
                : null;
        });
    }

    /**
     * Check if the user has completed their profile.
     * 
     * Verifies that birth_date, address, and phone are present,
     * AND either license_number OR medical_certificate_code is provided.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<bool, never>
     */
    protected function hasCompletedProfile(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                return !empty($this->birth_date) &&
                    !empty($this->address) &&
                    !empty($this->phone);
            }
        );
    }

    public function connectedAccounts()
    {
        return $this->hasMany(ConnectedAccount::class);
    }

    /**
     * Get the user's license number from the associated member record.
     */
    public function getLicenseNumberAttribute()
    {
        return $this->member ? $this->member->adh_license : null;
    }

    /**
     * Get the clubs that the user belongs to.
     */
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_user', 'user_id', 'club_id')
            ->withPivot('role', 'status')
            ->withTimestamps();
    }

    /**
     * Determine if the user is allowed to reset their password.
     * A user can reset their password if they have a local password set
     * and no connected social accounts.
     *
     * @return bool
     */
    public function canResetPassword(): bool
    {
        return $this->password_is_set && !$this->connectedAccounts()->exists();
    }

    /**
     * Check if the user is a club leader.
     * Uses the club_users pivot table to check if user has a manager role
     * 
     * Check if the user is a club leader/manager.
     * A user is considered a club leader if they created any club (created_by column).
     * 
     * @return bool
     */
    public function isClubLeader(): bool
    {
        if (!$this->id) {
            return false;
        }

        // Check if user is the creator of any club
        $isCreator = \DB::table('clubs')
            ->where('created_by', $this->id)
            ->exists();

        if ($isCreator) {
            return true;
        }

        // Check if user has the responsable-club role
        if ($this->hasRole('responsable-club')) {
            return true;
        }

        // Check if user is a manager of any club in the pivot table
        $isManager = $this->clubs()
            ->wherePivot('role', 'manager')
            ->wherePivot('status', 'approved')
            ->exists();

        return $isManager;
    }

    /**
     * Check if user has valid credentials (PPS OR License).
     * 
     * @return bool
     */
    public function hasValidCredentials(): bool
    {
        $hasLicense = false;
        $hasPPS = false;
        $now = now();

        // Check License
        if ($this->member && $this->member->adh_end_validity) {
            $hasLicense = $now->lessThan($this->member->adh_end_validity);
        }

        // Check PPS
        if ($this->medicalDoc && $this->medicalDoc->doc_end_validity) {
            $hasPPS = $now->lessThan($this->medicalDoc->doc_end_validity);
        }

        return $hasLicense || $hasPPS;
    }

    /**
     * Get team invitations received by this user.
     */
    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'invitee_id');
    }

    /**
     * Get team invitations received by this user's email.
     */
    public function teamInvitationsByEmail()
    {
        return $this->hasMany(TeamInvitation::class, 'email', 'email');
    }

    /**
     * Get pending team invitations for this user.
     */
    public function pendingTeamInvitations()
    {
        return $this->teamInvitations()->pending();
    }

    /**
     * Get team invitations sent by this user.
     */
    public function sentTeamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'inviter_id');
    }

    /**
     * Get club invitations received by this user.
     */
    public function clubInvitations()
    {
        return $this->hasMany(ClubInvitation::class, 'email', 'email');
    }

    /**
     * Get pending club invitations for this user.
     */
    public function pendingClubInvitations()
    {
        return $this->clubInvitations()->pending();
    }

    /**
     * Get race registrations created by this user.
     */
    public function raceRegistrations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RaceRegistration::class, 'user_id');
    }

    /**
     * Get temporary team invitations received by this user.
     */
    public function temporaryTeamInvitations()
    {
        return $this->hasMany(TemporaryTeamInvitation::class, 'email', 'email');
    }

    /**
     * Get all pending invitations (team + club + temporary team) for this user.
     */
    public function getAllPendingInvitations(): array
    {
        return [
            'team' => $this->teamInvitations()->pending()->get()
                ->merge($this->teamInvitationsByEmail()->pending()->get())
                ->unique('id')
                ->load(['inviter', 'team.users', 'race']),
            'temp_team' => $this->temporaryTeamInvitations()
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with(['inviter', 'registration.race', 'registration.user'])
                ->get(),
            'club' => $this->pendingClubInvitations()->with(['inviter', 'club'])->get(),
        ];
    }


}
