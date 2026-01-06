<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Team Age Requirements
    |--------------------------------------------------------------------------
    |
    | These values define the age requirements for team composition.
    | Rules: A <= B <= C
    |
    | - age_min (A): Minimum age for any participant
    | - age_intermediate (B): Age threshold - participants under this need supervision
    | - age_adult (C): Adult/supervisor age - required if any participant is under B
    |
    | Example with defaults (12, 16, 18):
    | - Everyone must be at least 12 years old
    | - If someone is under 16, the team must have someone 18+
    |
    */

    'age_min' => env('TEAM_AGE_MIN', 12),
    'age_intermediate' => env('TEAM_AGE_INTERMEDIATE', 16),
    'age_adult' => env('TEAM_AGE_ADULT', 18),

    /*
    |--------------------------------------------------------------------------
    | Team Size Limits
    |--------------------------------------------------------------------------
    |
    | Define the minimum and maximum number of participants per team.
    |
    */

    'min_members' => env('TEAM_MIN_MEMBERS', 2),
    'max_members' => env('TEAM_MAX_MEMBERS', 5),
];
