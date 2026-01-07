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
     * Get the team leader (member who created the team).
     */
    public function leader()
    {
        return $this->belongsTo(Member::class, 'adh_id', 'adh_id');
    }

    /**
     * Relationship with the member model.
     */
    public function members()
    {
        return $this->belongsToMany(
            Member::class,
            'team_members',
            'equ_id',
            'adh_id',
            'equ_id',
            'adh_id'
        );
    }
}
