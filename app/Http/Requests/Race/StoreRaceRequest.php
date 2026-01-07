<?php

namespace App\Http\Requests\Race;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Race;

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
        // Check if the selected type is competitive
        $typeId = $this->input('type');
        $isCompetitive = false;
        if ($typeId) {
            $type = \App\Models\ParamType::find($typeId);
            if ($type && (strtolower($type->typ_name) === 'compétitif' || strtolower($type->typ_name ?? '') === 'competitif')) {
                $isCompetitive = true;
            }
        }

        $startDateRules = ['required', 'date'];
        // Only enforce future date for new races
        if ($this->isMethod('post')) {
            $startDateRules[] = 'after_or_equal:today';
        }

        $rules = [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'startDate' => $startDateRules,
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
            // priceMinor is required only for non-competitive races
            'priceMinor' => $isCompetitive ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'priceMajorAdherent' => ['nullable', 'numeric', 'min:0'],
            'priceMinorAdherent' => ['nullable', 'numeric', 'min:0'],
            'responsableId' => ['required', 'integer', 'exists:users,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'raid_id' => ['nullable', 'integer'],
        ];

        // Add raid date constraints if a raid is provided
        $raidId = $this->input('raid_id');
        if ($raidId) {
            $raid = \App\Models\Raid::find($raidId);
            if ($raid) {
                $raidStartDate = $raid->raid_date_start ? $raid->raid_date_start->format('Y-m-d') : null;
                $raidEndDate = $raid->raid_date_end ? $raid->raid_date_end->format('Y-m-d') : null;
                
                if ($raidStartDate) {
                    $rules['startDate'][] = 'after_or_equal:' . $raidStartDate;
                }
                if ($raidEndDate) {
                    // Start date must be before raid ends
                    $rules['startDate'][] = 'before_or_equal:' . $raidEndDate;
                    // End date must be before raid ends
                    $rules['endDate'][] = 'before_or_equal:' . $raidEndDate;
                }
            }
        }

        return $rules;
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
            'startDate.after_or_equal' => 'La date de départ doit être comprise dans les dates du raid.',
            'startDate.before_or_equal' => 'La date de départ doit être avant la fin du raid.',
            'endDate.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
            'endDate.before_or_equal' => 'La date de fin doit être avant la fin du raid.',
            'duration.regex' => 'La durée doit être au format h:mm (ex: 2:30).',
            'minParticipants.required' => 'Le nombre minimum de participants est obligatoire.',
            'maxParticipants.required' => 'Le nombre maximum de participants est obligatoire.',
            'maxParticipants.gte' => 'Le nombre maximum doit être supérieur ou égal au minimum.',
            'difficulty.required' => 'La difficulté est obligatoire.',
            'type.exists' => 'Le type sélectionné est invalide.',
            'priceMajor.required' => 'Le prix pour les majeurs est obligatoire.',
            'priceMinor.required' => 'Le prix pour les mineurs est obligatoire.',
            'priceMajorAdherent.numeric' => 'Le prix adhérent pour les majeurs doit être un nombre.',
            'priceMinorAdherent.numeric' => 'Le prix adhérent pour les mineurs doit être un nombre.',
            'responsableId.required' => 'Le responsable de la course est obligatoire.',
            'responsableId.exists' => 'Le responsable sélectionné est invalide.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne doit pas dépasser 5 Mo.',
        ];
    }
}
