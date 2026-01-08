<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Inertia\Inertia;

/**
 * Controller for displaying race details and list
 */
class VisuRaceController extends Controller
{
    /**
     * Display a listing of all races.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $races = Race::with(['raid.club', 'type', 'organizer.user'])
            ->orderBy('race_date_start', 'desc')
            ->get()
            ->map(function ($race) {
                return [
                    'race_id' => $race->race_id,
                    'race_name' => $race->race_name,
                    'race_description' => $race->race_description,
                    'race_date_start' => $race->race_date_start?->toIso8601String(),
                    'race_date_end' => $race->race_date_end?->toIso8601String(),
                    'race_difficulty' => $race->race_difficulty,
                    'race_duration_minutes' => $race->race_duration_minutes,
                    'type' => $race->type?->typ_name ?? 'Classique',
                    'image_url' => $race->image_url ? '/storage/' . $race->image_url : null,
                    'price_major' => $race->price_major,
                    'price_minor' => $race->price_minor,
                    'location' => $race->raid ? trim(($race->raid->raid_city ?? '') . ' ' . ($race->raid->raid_postal_code ?? '')) : 'Lieu à définir',
                    'status' => $this->getRaceStatus($race),
                    'isOpen' => $race->isOpen(),
                    'raid' => $race->raid ? [
                        'id' => $race->raid->raid_id,
                        'name' => $race->raid->raid_name,
                        'city' => $race->raid->raid_city,
                        'club' => $race->raid->club ? [
                            'id' => $race->raid->club->club_id,
                            'name' => $race->raid->club->club_name,
                        ] : null,
                    ] : null,
                    'organizer' => $race->organizer?->user ? [
                        'name' => trim($race->organizer->user->first_name . ' ' . $race->organizer->user->last_name),
                    ] : null,
                ];
            });

        return Inertia::render('Race/Index', [
            'races' => $races,
        ]);
    }

    /**
     * Display the specified race.
     *
     * @param int $id
     * @return \Inertia\Response
     */
    public function show(int $id)
    {
        // Find the race by ID with related data including age categories
        $race = Race::with([
            'organizer.user',
            'raid.registrationPeriod',
            'raid.club',
            'type',
            'teamParams',
            'categorieAges.ageCategorie'
        ])->find($id);

        // If race not found, return error page
        if (!$race) {
            return Inertia::render('Race/VisuRace', [
                'race' => null,
                'error' => 'Course non trouvée',
                'errorMessage' => "Aucune course ne correspond à l'identifiant #" . $id . ". Elle a peut-être été supprimée ou l'ID est incorrect.",
            ]);
        }

        $user = auth()->user();
        // Admin can manage all races, otherwise check if user is race organizer or club manager
        $isRaceManager = $user && ($user->hasRole('admin') || ($race->organizer && $user->adh_id === $race->organizer->adh_id) || ($race->raid && $race->raid->club && $race->raid->club->hasManager($user)));

        // Fetch participants for managers
        $participants = [];
        if ($isRaceManager) {
            $participants = \DB::table('registration')
                ->join('teams', 'registration.equ_id', '=', 'teams.equ_id')
                ->join('has_participate', 'teams.equ_id', '=', 'has_participate.equ_id')
                ->join('members', 'has_participate.adh_id', '=', 'members.adh_id')
                ->join('users', 'members.adh_id', '=', 'users.adh_id')
                ->leftJoin('medical_docs', 'users.doc_id', '=', 'medical_docs.doc_id')
                ->where('registration.race_id', $race->race_id)
                ->select([
                    'users.first_name', 
                    'users.last_name', 
                    'users.email',
                    'members.adh_license',
                    'members.adh_end_validity as license_expiry',
                    'medical_docs.doc_num_pps as pps_number',
                    'medical_docs.doc_end_validity as pps_expiry',
                    'registration.reg_validated',
                    'teams.equ_name'
                ])
                ->get()
                ->map(function($p) {
                    $now = now();
                    $p->is_license_valid = $p->license_expiry && $now->lessThan($p->license_expiry);
                    $p->is_pps_valid = $p->pps_expiry && $now->lessThan($p->pps_expiry);
                    return $p;
                });
        }

        // Transform data for frontend
        $raceData = [
            'id' => $race->race_id,
            'raidId' => $race->raid_id,
            'title' => $race->race_name,
            'description' => $race->raid?->raid_description ?? 'Aucune description disponible.',
            'location' => $race->raid?->raid_location ?? 'Lieu à définir',
            'latitude' => $race->raid?->raid_latitude ?? 48.8566,
            'longitude' => $race->raid?->raid_longitude ?? 2.3522,
            'raceDate' => $race->race_date_start?->toIso8601String(),
            'endDate' => $race->race_date_end?->toIso8601String(),
            'duration' => $race->race_duration_minutes ? floor($race->race_duration_minutes / 60) . ':' . str_pad((int)($race->race_duration_minutes % 60), 2, '0', STR_PAD_LEFT) : '0:00',
            'raceType' => $race->type?->typ_name ?? 'Classique',
            'difficulty' => $race->race_difficulty ?? 'Moyen',
            'status' => $this->getRaceStatus($race),
            'isOpen' => $race->isOpen(),
            'registrationUpcoming' => $race->isRegistrationUpcoming(),
            'imageUrl' => $race->image_url ? '/storage/' . $race->image_url : null,
            'description' => $race->race_description ?? 'Aucune description disponible.',
            'maxParticipants' => 100, 
            'registeredCount' => \DB::table('registration')->where('race_id', $race->race_id)->count(),
            'organizer' => [
                'id' => $race->organizer?->user?->id,
                'name' => trim(($race->organizer?->adh_firstname ?? '') . ' ' . ($race->organizer?->adh_lastname ?? '')) ?: ($race->organizer?->user?->name ?? 'Organisateur'),
                'email' => $race->organizer?->user?->email ?? ''
            ],
            'ageCategories' => $race->categorieAges->map(fn($pc) => [
                'id' => $pc->ageCategorie->id,
                'nom' => $pc->ageCategorie->nom,
                'age_min' => $pc->ageCategorie->age_min,
                'age_max' => $pc->ageCategorie->age_max,
            ])->toArray(),
            'raid' => $race->raid ? [
                'id' => $race->raid->raid_id,
                'nom' => $race->raid->raid_name,
                'description' => $race->raid->raid_description,
                'location' => trim(($race->raid->raid_street ?? '') . ' ' . ($race->raid->raid_city ?? '') . ' ' . ($race->raid->raid_postal_code ?? '')) ?: 'Lieu à définir',
                'latitude' => $race->raid->raid_latitude,
                'longitude' => $race->raid->raid_longitude,
                'dateStart' => $race->raid->raid_date_start?->toIso8601String(),
                'dateEnd' => $race->raid->raid_date_end?->toIso8601String(),
                'club' => $race->raid->club ? [
                    'id' => $race->raid->club->club_id,
                    'nom' => $race->raid->club->club_name,
                ] : null,
            ] : null,
            'categories' => [],
            'priceMajor' => $race->price_major,
            'priceMinor' => $race->price_minor,
            'priceAdherent' => $race->price_adherent,
            'minTeams' => $race->teamParams?->pae_nb_min ?? 1,
            'maxTeams' => $race->teamParams?->pae_nb_max ?? 1,
            'maxPerTeam' => $race->teamParams?->pae_team_count_max ?? 1,
            'createdAt' => $race->created_at?->toIso8601String(),
            'updatedAt' => $race->updated_at?->toIso8601String(),
        ];

        return Inertia::render('Race/VisuRace', [
            'race' => $raceData,
            'isManager' => $isRaceManager,
            'participants' => $participants,
        ]);
    }

    /**
     * Determine race status based on dates.
     *
     * @param Race $race
     * @return string
     */
    private function getRaceStatus(Race $race): string
    {
        $now = now();

        if ($race->race_date_end && $now->isAfter($race->race_date_end)) {
            return 'completed';
        }

        if ($race->race_date_start && $now->isAfter($race->race_date_start)) {
            return 'ongoing';
        }

        return 'planned';
    }
}
