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
            'name' => ['required', 'string', 'max:128'],
            'event_start_date' => ['required', 'date', 'after:now'],
            'event_end_date' => ['required', 'date', 'after:event_start_date'],
            'registration_start_date' => ['required', 'date', 'after:now'],
            'registration_end_date' => ['required', 'date', 'after:registration_start_date', 'before:event_start_date'],
            
            // Placeholder fields (nullable until related tables are created)
            'adherent_id' => ['nullable', 'integer'],
            'club_id' => ['nullable', 'string', 'max:32'],
            'periode_inscription_id' => ['nullable', 'integer'],
            
            // Contact and address fields (nullable as placeholders)
            'contact' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'string'], // Will be base64 or file path
            'address' => ['nullable', 'string', 'max:128'],
            'postal_code' => ['nullable', 'string', 'max:128'],
            'number' => ['nullable', 'string', 'max:128'],
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
            'name' => 'raid name',
            'event_start_date' => 'event start date',
            'event_end_date' => 'event end date',
            'registration_start_date' => 'registration start date',
            'registration_end_date' => 'registration end date',
            'adherent_id' => 'adherent',
            'club_id' => 'club',
            'periode_inscription_id' => 'registration period',
            'contact' => 'contact',
            'website_url' => 'website URL',
            'image' => 'image',
            'address' => 'address',
            'postal_code' => 'postal code',
            'number' => 'number',
        ];
    }
}
