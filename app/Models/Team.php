<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Team model - Represents a team/équipe
 */
class Team extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'teams';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'equ_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'equ_name',
        'equ_image',
        'user_id',
    ];

    /**
     * Get the users that belong to this team.
     * Uses the has_participate pivot table.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'has_participate', 'equ_id', 'id_users');
    }

    /**
     * Get the team leader (user).
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
