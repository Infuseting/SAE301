<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class StoreRaidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Add proper authorization logic (e.g., role-based)
    }

    /**
     * Prepare data for validation.
     * Automatically set clu_id from user's club
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
            'raid_description' => ['required', 'string'],
            'raid_date_start' => ['required', 'date', 'after:now'],
            'raid_date_end' => ['required', 'date', 'after:raid_date_start'],
            
            // Inscription period dates
            'ins_start_date' => ['required', 'date', 'before:raid_date_start'],
            'ins_end_date' => ['required', 'date', 'after:ins_start_date', 'before:raid_date_start'],
            
            // Foreign keys (required)
            'adh_id' => ['required', 'integer', 'exists:members,adh_id'],
            'clu_id' => ['required', 'integer', 'exists:clubs,club_id'],
            
            // Required fields
            'raid_contact' => ['required', 'string', 'max:100'],
            'raid_street' => ['required', 'string', 'max:100'],
            'raid_city' => ['required', 'string', 'max:100'],
            'raid_postal_code' => ['required', 'string', 'max:20'],
            'raid_number' => ['required', 'integer'],
            
            // Optional fields
            'raid_site_url' => ['nullable', 'url', 'max:255'],
            'raid_image' => ['nullable', 'string', 'max:255'],
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
            'raid_number' => 'number',
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
