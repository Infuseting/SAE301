<?php

namespace App\Http\Requests;

use App\Rules\FfcoLicenseNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for completing user profile during onboarding.
 *
 * Handles validation of required profile fields including personal information,
 * contact details, and optional license number with FFCO format validation.
 */
class ProfileCompletionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'license_number' => ['nullable', new FfcoLicenseNumber()],
        ];
    }
}
