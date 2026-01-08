<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\FfcoLicenseNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating user profile information.
 *
 * Handles validation of profile update data including personal information,
 * contact details, and optional license number with FFCO format validation.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * Convert empty license_number to null so nullable validation works correctly.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('license_number') && empty(trim($this->license_number ?? ''))) {
            $this->merge([
                'license_number' => null,
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['sometimes', 'boolean'],
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'license_number' => ['sometimes', 'nullable', new FfcoLicenseNumber()],
            'photo' => ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,webp'], // 2MB Max (Client compresses 8MB -> 2MB)
        ];
    }
}
