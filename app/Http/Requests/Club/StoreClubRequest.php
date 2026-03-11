<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Club;

/**
 * Form request for creating a new club.
 */
class StoreClubRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only adherent and admin users can create clubs.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Club::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'club_name' => 'required|string|max:100',
            'club_street' => 'required|string|max:100',
            'club_city' => 'required|string|max:100',
            'club_postal_code' => 'required|string|max:20',
            'ffso_id' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'club_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'club_name.required' => 'Club name is required',
            'club_name.max' => 'Club name must not exceed 100 characters',
            'club_street.required' => 'Club street address is required',
            'club_street.max' => 'Club street address must not exceed 100 characters',
            'club_city.required' => 'Club city is required',
            'club_city.max' => 'Club city must not exceed 100 characters',
            'club_postal_code.required' => 'Club postal code is required',
            'club_postal_code.max' => 'Club postal code must not exceed 20 characters',
            'ffso_id.required' => 'FFSO ID is required',
            'ffso_id.max' => 'FFSO ID must not exceed 50 characters',
            'description.max' => 'Description must not exceed 1000 characters',
            'club_image.image' => 'The file must be a valid image',
            'club_image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, webp',
            'club_image.max' => 'The image may not be greater than 5 MB',
        ];
    }
}
