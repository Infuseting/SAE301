<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            
            // Foreign keys (required)
            'adh_id' => ['required', 'integer'],
            'clu_id' => ['required', 'integer'],
            'ins_id' => ['required', 'integer'],
            
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
            'adh_id' => 'organizer',
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
}
