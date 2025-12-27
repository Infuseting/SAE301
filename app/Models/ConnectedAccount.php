<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
