<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $member = \App\Models\Member::create([
            'adh_license' => 'PENDING-' . \Illuminate\Support\Str::random(8),
            'adh_end_validity' => now()->addYear(),
            'adh_date_added' => now(),
        ]);

        $medicalDoc = \App\Models\MedicalDoc::create([
            'doc_num_pps' => 'PENDING',
            'doc_end_validity' => now()->addYear(),
            'doc_date_added' => now(),
        ]);

        return User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'adh_id' => $member->adh_id,
            'doc_id' => $medicalDoc->doc_id,
        ]);
    }
}
