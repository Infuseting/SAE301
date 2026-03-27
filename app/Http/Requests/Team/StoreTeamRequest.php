<?php

namespace App\Http\Requests\Team;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Controllers\Api\ApiResponseTrait;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Form Request for storing teams.
 *
 * Handles validation for both API (JSON) and web (form) submissions.
 */
class StoreTeamRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:32',
            'image' => 'nullable|image|max:2048',
            'teammates' => 'nullable|array',
            'teammates.*.id' => 'integer|exists:users,id',
            'emailInvites' => 'nullable|array',
            'emailInvites.*' => 'email',
            'join_team' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return trans('validation.team');
    }

    /**
     * Get the validator instance (for additional validation).
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return \Illuminate\Validation\Validator
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): \Illuminate\Validation\Validator
    {
        $validator->after(function ($validator) {
            // Check if team has at least one participant
            $joinTeam = $this->input('join_team') ?? false;
            $teammates = $this->input('teammates') ?? [];
            $hasTeammates = !empty($teammates);

            if (!$joinTeam && !$hasTeammates) {
                $validator->errors()->add(
                    'teammates',
                    'L\'équipe doit avoir au moins un participant. Cochez "Je participe" ou ajoutez des coéquipiers.'
                );
            }
        });

        return $validator;
    }

    /**
     * Handle a failed validation attempt.
     *
     * For API requests, return JSON error response.
     * For web requests, use the default behavior.
     */
    protected function failedValidation(Validator $validator): void
    {
        // For API JSON requests
        if ($this->wantsJson()) {
            throw new ValidationException(
                $validator,
                $this->unprocessableContentResponse('Validation failed', $validator->errors())
            );
        }

        // For web requests (default behavior)
        parent::failedValidation($validator);
    }
}



