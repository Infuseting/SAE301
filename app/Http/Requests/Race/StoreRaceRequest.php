<?php

namespace App\Http\Requests\Race;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new race.
 */
class StoreRaceRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'startDate' => ['required', 'date', 'after:today'],
            'startTime' => ['required', 'date_format:H:i'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'endTime' => ['required', 'date_format:H:i', function($attribute, $value, $fail) {
                $startDate = $this->input('startDate');
                $endDate = $this->input('endDate');
                $startTime = $this->input('startTime');
                if ($startDate === $endDate) {
                    if (strtotime($value) <= strtotime($startTime)) {
                        $fail('L\'heure de fin doit être après l\'heure de début pour une même journée.');
                    }
                }
            }],
            'duration' => ['nullable', 'string'],
            'minParticipants' => ['required', 'integer', 'min:1'],
            'maxParticipants' => ['required', 'integer', 'gte:minParticipants'],
            'maxPerTeam' => ['required', 'integer', 'min:1'],
            'difficulty' => ['required', 'string', 'max:50'],
            'type' => ['required', 'integer', 'exists:param_type,typ_id'],
            'minTeams' => ['required', 'integer', 'min:1'],
            'maxTeams' => ['required', 'integer', 'gte:minTeams'],
            'licenseDiscount' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'priceMajor' => ['required', 'numeric', 'min:0'],
            'priceMinor' => ['required', 'numeric', 'min:0'],
            'priceMajorAdherent' => ['required', 'numeric', 'min:0'],
            'priceMinorAdherent' => ['required', 'numeric', 'min:0'],
            'responsableId' => ['required', 'integer', 'exists:users,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le nom de la course est obligatoire.',
            'title.max' => 'Le nom de la course ne peut pas dépasser 100 caractères.',
            'startDate.required' => 'La date de départ est obligatoire.',
            'startDate.after' => 'La date de départ doit être dans le futur.',
            'minParticipants.required' => 'Le nombre minimum de participants est obligatoire.',
            'maxParticipants.required' => 'Le nombre maximum de participants est obligatoire.',
            'maxParticipants.gte' => 'Le nombre maximum doit être supérieur ou égal au minimum.',
            'difficulty.exists' => 'La difficulté sélectionnée est invalide.',
            'type.exists' => 'Le type sélectionné est invalide.',
            'responsableId.required' => 'Le responsable de la course est obligatoire.',
            'responsableId.exists' => 'Le responsable sélectionné est invalide.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne doit pas dépasser 5 Mo.',
        ];
    }
}
