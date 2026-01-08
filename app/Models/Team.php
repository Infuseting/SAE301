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
        'adh_id',
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
     * Get the team leader (user who created the team).
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'adh_id', 'id');
    }

    /**
     * Get pending invitations for this team.
     */
    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id', 'equ_id');
    }

    /**
     * Get pending invitations.
     */
    public function pendingInvitations()
    {
        return $this->invitations()->pending();
    }

    /**
     * Get race registrations using this team.
     */
    public function registrations()
    {
        return $this->hasMany(RaceRegistration::class, 'team_id', 'equ_id');
    }

    /**
     * Check if team meets race requirements.
     * 
     * @param Race $race
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateForRace(Race $race): array
    {
        $errors = [];
        $memberCount = $this->users()->count();

        if ($race->teamParams) {
            $minMembers = $race->teamParams->pae_nb_min ?? 1;
            $maxMembers = $race->teamParams->pae_nb_max ?? 999;

            if ($memberCount < $minMembers) {
                $errors[] = "L'équipe doit avoir au moins {$minMembers} membres";
            }
            if ($memberCount > $maxMembers) {
                $errors[] = "L'équipe ne peut pas avoir plus de {$maxMembers} membres";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
