<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $table = 'members';
    protected $primaryKey = 'adh_id';

    protected $fillable = [
        'adh_license',
        'adh_end_validity',
        'adh_date_added',
    ];

    protected $casts = [
        'adh_end_validity' => 'date',
        'adh_date_added' => 'date',
    ];

    /**
     * Get the teams led by this member.
     */
    public function leaderTeams()
    {
        return $this->hasMany(Team::class, 'adh_id', 'adh_id');
    }

    /**
     * Get the teams this member belongs to via has_participate.
     */
    public function teams()
    {
        return $this->belongsToMany(
            Team::class,
            'has_participate',
            'adh_id',
            'equ_id',
            'adh_id',
            'equ_id'
        );
    }
    
    /**
     * Get the user associated with this member.
     * A member has one user (one-to-one relationship)
     */
    public function user()
    {
        return $this->hasOne(User::class, 'adh_id', 'adh_id');

    }
}
