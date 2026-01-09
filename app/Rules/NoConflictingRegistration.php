<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Team;
use App\Models\Race;
use Illuminate\Support\Facades\DB;

/**
 * Validates that a user or team members do not have conflicting race registrations
 * within the same registration period.
 */
class NoConflictingRegistration implements ValidationRule
{
    protected Race $race;
    protected $conflictingUser = null;

    /**
     * Create a new rule instance.
     *
     * @param Race $race The race being registered for
     */
    public function __construct(Race $race)
    {
        $this->race = $race;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get the team
        $team = Team::where('equ_id', $value)->first();
        
        if (!$team) {
            $fail(__('messages.team_not_found'));
            return;
        }

        // Get registration period for the current race
        $registrationPeriod = $this->race->raid?->registrationPeriod;
        
        if (!$registrationPeriod) {
            // If no registration period, skip validation
            return;
        }

        // Get all team members (including the leader)
        $teamMemberIds = $team->users()->pluck('users.id')->toArray();
        
        if (empty($teamMemberIds)) {
            $fail(__('messages.team_has_no_members'));
            return;
        }

        // Find races within the same registration period
        $racesInSamePeriod = Race::whereHas('raid', function ($query) use ($registrationPeriod) {
            $query->where('ins_id', $registrationPeriod->ins_id);
        })
        ->where('race_id', '!=', $this->race->race_id)
        ->pluck('race_id')
        ->toArray();

        if (empty($racesInSamePeriod)) {
            // No other races in the same period, validation passes
            return;
        }

        // Check if any team member is already registered in another race during this period
        foreach ($teamMemberIds as $memberId) {
            $conflictingRegistration = DB::table('registration')
                ->join('has_participate', 'registration.reg_id', '=', 'has_participate.reg_id')
                ->whereIn('registration.race_id', $racesInSamePeriod)
                ->where('has_participate.id_users', $memberId)
                ->select('registration.race_id', 'has_participate.id_users', 'registration.reg_id')
                ->first();

            if ($conflictingRegistration) {
                // Find which user has the conflict
                $conflictingUserId = $conflictingRegistration->id_users;
                $conflictingUser = \App\Models\User::find($conflictingUserId);
                
                // Get the race name
                $conflictingRace = Race::find($conflictingRegistration->race_id);
                
                // Check if it's the current user (leader of the team being registered)
                $currentUser = auth()->user();
                
                if ($currentUser && $conflictingUserId == $currentUser->id) {
                    $fail(__('messages.you_already_registered_in_period', [
                        'race' => $conflictingRace?->race_name ?? 'une course'
                    ]));
                } else {
                    $fail(__('messages.team_member_already_registered', [
                        'user' => $conflictingUser ? $conflictingUser->first_name . ' ' . $conflictingUser->last_name : 'Un membre',
                        'race' => $conflictingRace?->race_name ?? 'une course'
                    ]));
                }
                
                return;
            }
        }
    }
}
