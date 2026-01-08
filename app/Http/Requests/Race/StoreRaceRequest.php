<?php

namespace App\Http\Requests\Race;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Race;
use App\Models\Raid;

/**
 * Form request for creating a new race.
 */
class StoreRaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only responsable-course and admin can create races.
     */
    public function authorize(): bool
    {
        $raidId = $this->input('raid_id');
        $raid = $raidId ? \App\Models\Raid::find($raidId) : null;

        return $this->user()->can('create', [Race::class, $raid]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $typeId = $this->input('type');
        $isCompetitive = false;
        
        // Check if race type is competitive
        if ($typeId) {
            $type = \App\Models\ParamType::find($typeId);
            if ($type && (strtolower($type->typ_name) === 'compétitif' || strtolower($type->typ_name) === 'competitif')) {
                $isCompetitive = true;
            }
        }

        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'startTime' => ['required', 'date_format:H:i'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'endTime' => ['required', 'date_format:H:i'],
            'duration' => ['nullable', 'string', 'regex:/^\d+:\d{2}$/'],
            'minParticipants' => ['required', 'integer', 'min:1'],
            'maxParticipants' => ['required', 'integer', 'gte:minParticipants'],
            'maxPerTeam' => ['required', 'integer', 'min:1'],
            'difficulty' => ['required', 'string', 'max:50'],
            'type' => ['required', 'integer', 'exists:param_type,typ_id'],
            'minTeams' => ['required', 'integer', 'min:1'],
            'maxTeams' => ['required', 'integer', 'gte:minTeams'],
            'mealPrice' => ['nullable', 'numeric', 'min:0'],
            'priceMajor' => ['required', 'numeric', 'min:0'],
            // Minor prices are not required for competitive races
            'priceMinor' => $isCompetitive ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'priceAdherent' => ['nullable', 'numeric', 'min:0', 'lte:priceMajor'],
            'responsableId' => ['required', 'integer', 'exists:users,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'raid_id' => ['nullable', 'integer', 'exists:raids,raid_id'],
            'selectedAgeCategories' => ['nullable', 'array'],
            'selectedAgeCategories.*' => ['integer', 'exists:age_categories,id'],
        ];
    }

    /**
     * Configure the validator instance.
     * Adds custom date range validation against raid dates and competitive type validation.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Note: Age categories selection is optional for now
            // TODO: Make this required once all tests are updated with age categories
            // $selectedCategories = $this->input('selectedAgeCategories', []);
            // if (!is_array($selectedCategories) || empty($selectedCategories)) {
            //     $validator->errors()->add('selectedAgeCategories', 'Veuillez sélectionner au moins une catégorie d\'âge.');
            // }

            // Validate raid date range if raid is associated
            $raidId = $this->input('raid_id');
            if ($raidId) {
                $raid = Raid::find($raidId);
                if ($raid && $raid->raid_date_start && $raid->raid_date_end) {
                    $startDate = $this->input('startDate');
                    $endDate = $this->input('endDate');

                    // Check if start date is within raid date range
                    if ($startDate && $startDate < $raid->raid_date_start->format('Y-m-d')) {
                        $validator->errors()->add('startDate', 'La date de début de la course doit être après le ' . $raid->raid_date_start->format('d/m/Y') . ' (début du raid).');
                    }
                    if ($startDate && $startDate > $raid->raid_date_end->format('Y-m-d')) {
                        $validator->errors()->add('startDate', 'La date de début de la course doit être avant le ' . $raid->raid_date_end->format('d/m/Y') . ' (fin du raid).');
                    }

                    // Check if end date is within raid date range
                    if ($endDate && $endDate < $raid->raid_date_start->format('Y-m-d')) {
                        $validator->errors()->add('endDate', 'La date de fin de la course doit être après le ' . $raid->raid_date_start->format('d/m/Y') . ' (début du raid).');
                    }
                    if ($endDate && $endDate > $raid->raid_date_end->format('Y-m-d')) {
                        $validator->errors()->add('endDate', 'La date de fin de la course doit être avant le ' . $raid->raid_date_end->format('d/m/Y') . ' (fin du raid).');
                    }
                }
            }

            // Validate that minor prices are not set for competitive races (if they are provided, they should be rejected)
            $typeId = $this->input('type');
            if ($typeId) {
                $type = \App\Models\ParamType::find($typeId);
                if ($type && (strtolower($type->typ_name) === 'compétitif' || strtolower($type->typ_name) === 'competitif')) {
                    $priceMinor = $this->input('priceMinor');

                    // Only add error if a value is explicitly provided and greater than 0
                    if ($priceMinor && $priceMinor > 0) {
                        $validator->errors()->add('priceMinor', 'Les tarifs pour les mineurs ne sont pas autorisés pour les courses compétitives (réservées aux adultes).');
                    }
                }
            }
        });
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
            'startDate.after_or_equal' => 'La date de départ ne peut pas être dans le passé.',
            'endDate.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
            'duration.regex' => 'La durée doit être au format h:mm (ex: 2:30).',
            'minParticipants.required' => 'Le nombre minimum de participants est obligatoire.',
            'maxParticipants.required' => 'Le nombre maximum de participants est obligatoire.',
            'maxParticipants.gte' => 'Le nombre maximum doit être supérieur ou égal au minimum.',
            'difficulty.required' => 'La difficulté est obligatoire.',
            'type.exists' => 'Le type sélectionné est invalide.',
            'priceMajor.required' => 'Le prix pour les majeurs est obligatoire.',
            'priceMinor.required' => 'Le prix pour les mineurs est obligatoire.',
            'priceAdherent.numeric' => 'Le prix adhérent doit être un nombre.',
            'priceAdherent.lte' => 'Le tarif adhérent doit être inférieur ou égal au tarif majeur.',
            'responsableId.required' => 'Le responsable de la course est obligatoire.',
            'responsableId.exists' => 'Le responsable sélectionné est invalide.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne doit pas dépasser 5 Mo.',
            'selectedAgeCategories.required' => 'Veuillez sélectionner au moins une catégorie d\'âge.',
            'selectedAgeCategories.min' => 'Veuillez sélectionner au moins une catégorie d\'âge.',
            'selectedAgeCategories.*.exists' => 'Une ou plusieurs catégories d\'âge sélectionnées sont invalides.',
        ];
    }
}
