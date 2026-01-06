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
        'name',
        'email',
        'password',
        'password_is_set',
        'birth_date',
        'address',
        'phone',
        'license_number',
        'medical_certificate_code',
        'is_public',
        'description',
        'profile_photo_path',
    ];

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
}
