<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new team.
 */
class StoreTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * All authenticated users can create teams.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:32',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'teammates' => 'nullable|array',
            'teammates.*.id' => 'integer|exists:users,id',
            'emailInvites' => 'nullable|array',
            'emailInvites.*' => 'email|max:255',
            'join_team' => 'nullable|boolean',
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
            'name.required' => 'Team name is required',
            'name.max' => 'Team name must not exceed 32 characters',
            'image.image' => 'The file must be a valid image',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, webp',
            'image.max' => 'The image may not be greater than 2 MB',
            'teammates.*.id.exists' => 'One or more teammates do not exist',
            'emailInvites.*.email' => 'One or more email addresses are invalid',
        ];
    }
}
