<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'teams';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'equ_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'equ_name',
        'equ_image',
        'adh_id',
    ];

    /**
     * Get the team leader (user who created the team).
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'adh_id', 'id');
    }

    /**
     * Get all users participating in the team.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'has_participate',
            'equ_id',
            'id_users'
        );
    }
}
