<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'license_number' => ['nullable', 'required_without:medical_certificate', 'string', 'max:50'],
            'medical_certificate' => ['nullable', 'required_without:license_number', 'string', 'max:50'],
        ];
    }
}
