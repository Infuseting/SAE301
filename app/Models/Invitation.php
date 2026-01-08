<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = ['inviter_id', 'invitee_id', 'equ_id', 'email', 'token', 'status', 'expires_at'];
}


