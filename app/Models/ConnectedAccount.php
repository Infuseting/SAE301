<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ConnectedAccount",
 *     title="Connected Account",
 *     description="Social Media Connected Account",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="provider", type="string", example="google"),
 *     @OA\Property(property="provider_id", type="string", example="123456789"),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ConnectedAccount extends Model
{
    protected $fillable = [
        'provider',
        'provider_id',
        'token',
        'secret',
        'refresh_token',
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
