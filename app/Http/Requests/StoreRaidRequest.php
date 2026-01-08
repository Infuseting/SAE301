<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class StoreRaidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only club leaders and admins can create raids.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        // Allow admins to bypass the club leader requirement
        if ($user && $user->hasRole('admin')) {
            return true;
        }
        
        return $user && $user->isClubLeader();
    }

    /**
     * Prepare data for validation.
     * Automatically set clu_id from user's club and convert postal_code to string
     */
    protected function prepareForValidation()
    {
        // Auto-assign club from authenticated user
        if (!$this->has('clu_id') || empty($this->clu_id)) {
            $userClub = \DB::table('clubs')
                ->where('created_by', auth()->id())
                ->first();
            
            if ($userClub) {
                $this->merge([
                    'clu_id' => $userClub->club_id,
                ]);
            }
        }

        // Convert postal_code to string if it's numeric
        if ($this->has('raid_postal_code') && is_numeric($this->raid_postal_code)) {
            $this->merge([
                'raid_postal_code' => (string) $this->raid_postal_code,
            ]);
        }

        // Force casting for IDs that must be integers
        if ($this->has('adh_id') && is_numeric($this->adh_id)) {
            $this->merge([
                'adh_id' => (int) $this->adh_id,
            ]);
        }
        
        if ($this->has('clu_id') && is_numeric($this->clu_id)) {
            $this->merge([
                'clu_id' => (int) $this->clu_id,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'raid_name' => ['required', 'string', 'max:100'],
            'raid_description' => ['nullable', 'string'],
            'raid_date_start' => ['required', 'date', 'after:now'],
            'raid_date_end' => ['required', 'date', 'after:raid_date_start'],
            
            // Inscription period dates
            'ins_start_date' => ['required', 'date', 'before:raid_date_start'],
            'ins_end_date' => ['required', 'date', 'after:ins_start_date', 'before:raid_date_start'],
            
            // Foreign keys (required)
            'adh_id' => [
                'required', 
                'integer', 
                'exists:members,adh_id',
                function($attribute, $value, $fail) {
                    $clubId = $this->input('clu_id');
                    if ($clubId) {
                        $isMemberOfClub = \DB::table('club_user')
                            ->join('users', 'club_user.user_id', '=', 'users.id')
                            ->where('club_user.club_id', $clubId)
                            ->where('users.adh_id', $value)
                            ->exists();
                        if (!$isMemberOfClub) {
                            $fail('Le responsable doit faire partie du club sélectionné.');
                        }
                    }
                }
            ],
            'clu_id' => ['required', 'integer', 'exists:clubs,club_id'],
            
            // Required fields
            'raid_contact' => ['required', 'email', 'max:100'],
            'raid_city' => ['required', 'string', 'max:100'],
            'raid_postal_code' => ['required', 'string', 'max:20'],
            
            // Optional address field
            'raid_street' => ['nullable', 'string', 'max:100'],
            
            // Optional fields
            'raid_site_url' => ['nullable', 'url', 'max:255'],
            'raid_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // 5MB max

            // Gestionnaire raid assignment (user id)
            'gestionnaire_raid_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'raid_name' => 'raid name',
            'raid_description' => 'description',
            'raid_date_start' => 'start date',
            'raid_date_end' => 'end date',
            'ins_start_date' => 'date de début d\'inscription',
            'ins_end_date' => 'date de fin d\'inscription',
            'adh_id' => 'responsable adhérent',
            'clu_id' => 'club',
            'ins_id' => 'registration period',
            'raid_contact' => 'contact',
            'raid_site_url' => 'website URL',
            'raid_image' => 'image',
            'raid_street' => 'street',
            'raid_city' => 'city',
            'raid_postal_code' => 'postal code',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'adh_id.required' => 'Le responsable est obligatoire.',
            'adh_id.exists' => 'Le responsable sélectionné n\'est pas un adhérent valide.',
            'ins_start_date.before' => 'La date de début d\'inscription doit être avant le début du raid.',
            'ins_end_date.after' => 'La date de fin d\'inscription doit être après la date de début d\'inscription.',
            'ins_end_date.before' => 'La date de fin d\'inscription doit être avant le début du raid.',
        ];
    }
}
