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
            'runnerParams',
            'categorieAges.ageCategory'
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

        // Fetch participants for managers using the new race_participants table
        $participants = [];
        if ($isRaceManager) {
            $participants = \DB::table('race_participants')
                ->join('registration', 'race_participants.reg_id', '=', 'registration.reg_id')
                ->join('teams', 'registration.equ_id', '=', 'teams.equ_id')
                ->join('users', 'race_participants.user_id', '=', 'users.id')
                ->leftJoin('members', 'users.adh_id', '=', 'members.adh_id')
                ->where('registration.race_id', $race->race_id)
                ->select([
                    'race_participants.rpa_id as participant_id',
                    'race_participants.reg_id',
                    'users.id as user_id',
                    'users.first_name', 
                    'users.last_name', 
                    'users.email',
                    'users.birth_date',
                    'members.adh_license',
                    'members.adh_end_validity as license_expiry',
                    'race_participants.pps_number',
                    'race_participants.pps_expiry',
                    'race_participants.pps_status',
                    'race_participants.pps_verified_at',
                    'race_participants.bib_number',
                    'registration.reg_validated',
                    'teams.equ_name',
                    'teams.equ_id'
                ])
                ->get()
                ->map(function($p) use ($race) {
                    $now = now();
                    $p->is_license_valid = $p->license_expiry && $now->lessThan($p->license_expiry);
                    $p->is_pps_valid = $p->pps_expiry && 
                                       $now->lessThan($p->pps_expiry) && 
                                       $p->pps_status === 'verified' &&
                                       !str_starts_with($p->pps_number ?? '', 'PENDING-');
                    
                    // Calculate participant price
                    $age = $p->birth_date ? $now->diffInYears($p->birth_date) : null;
                    $isCompetitive = $race->type && strtolower($race->type->typ_name) === 'competitif';
                    
                    if ($p->is_license_valid && $race->price_adherent !== null) {
                        // Licensed member price
                        $p->price = $race->price_adherent;
                        $p->price_category = 'Adhérent';
                    } elseif (!$isCompetitive && $age !== null && $age < 18 && $race->price_minor !== null) {
                        // Minor price (not for competitive races)
                        $p->price = $race->price_minor;
                        $p->price_category = 'Mineur';
                    } else {
                        // Adult price (default)
                        $p->price = $race->price_major ?? 0;
                        $p->price_category = 'Majeur';
                    }
                    
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
            'isCompetitive' => $race->type && strtolower($race->type->typ_name) === 'competitif',
            'difficulty' => $race->race_difficulty ?? 'Moyen',
            'status' => $this->getRaceStatus($race),
            'isOpen' => $race->isOpen(),
            'is_finished' => $race->isCompleted(),
            'registrationUpcoming' => $race->isRegistrationUpcoming(),
            'imageUrl' => $race->image_url ? '/storage/' . $race->image_url : null,
            'description' => $race->race_description ?? 'Aucune description disponible.',
            'maxParticipants' => 100, 
            'minMembers' => $race->teamParams?->pae_nb_min ?? 1,
            'maxMembers' => $race->teamParams?->pae_nb_max ?? 100,
            'maxTeams' => $race->teamParams?->pae_team_count_max ?? 100,
            'teamsCount' => $race->teams()->count(), // Existing teams registered
            'registeredCount' => \DB::table('registration')
                ->join('has_participate', 'registration.equ_id', '=', 'has_participate.equ_id')
                ->where('registration.race_id', $race->race_id)
                ->distinct('has_participate.id_users')
                ->count('has_participate.id_users'),
            'organizer' => [
                'id' => $race->organizer?->user?->id,
                'name' => trim(($race->organizer?->adh_firstname ?? '') . ' ' . ($race->organizer?->adh_lastname ?? '')) ?: ($race->organizer?->user?->name ?? 'Organisateur'),
                'email' => $race->organizer?->user?->email ?? ''
            ],
            'userTeams' => $user ? (function() use ($user) {
                \Log::info("Fetching teams for user: {$user->id}");
                
                // Only get teams where user is the leader
                $teams = \App\Models\Team::query()
                    ->where('user_id', $user->id)
                    ->get();

                \Log::info("Found " . $teams->count() . " teams.");
                
                return $teams->map(function($team) {
                    // Get members with their license status
                    $members = \DB::table('has_participate')
                        ->leftJoin('users', 'has_participate.id_users', '=', 'users.id')
                        ->leftJoin('members', 'users.adh_id', '=', 'members.adh_id')
                        ->where('has_participate.equ_id', $team->equ_id)
                        ->select('users.id', 'members.adh_license')
                        ->get();
                    
                    $licensedCount = $members->filter(fn($m) => !empty($m->adh_license))->count();
                    
                    return [
                        'id' => $team->equ_id,
                        'name' => $team->equ_name,
                        'members_count' => $members->count(),
                        'licensed_members_count' => $licensedCount,
                    ];
                })->values()->toArray();
            })() : [],
            'ageCategories' => $race->categorieAges->map(fn($pc) => [
                'id' => $pc->ageCategory->id,
                'nom' => $pc->ageCategory->nom,
                'age_min' => $pc->ageCategory->age_min,
                'age_max' => $pc->ageCategory->age_max,
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
            'registrationPeriod' => $race->raid?->registrationPeriod ? [
                'startDate' => $race->raid->registrationPeriod->ins_start_date?->toIso8601String(),
                'endDate' => $race->raid->registrationPeriod->ins_end_date?->toIso8601String(),
            ] : null,
            'categories' => [],
            'priceMajor' => $race->price_major,
            'priceMinor' => $race->price_minor,
            'priceAdherent' => $race->price_adherent,
            'minParticipants' => $race->runnerParams?->pac_nb_min ?? 0,
            'maxParticipants' => $race->runnerParams?->pac_nb_max ?? 100,
            'minTeams' => $race->teamParams?->pae_nb_min ?? 1,
            'maxTeams' => $race->teamParams?->pae_nb_max ?? 1,
            'minPerTeam' => $race->teamParams?->pae_team_count_min ?? 1,
            'maxPerTeam' => $race->teamParams?->pae_team_count_max ?? 1,
            'createdAt' => $race->created_at?->toIso8601String(),
            'updatedAt' => $race->updated_at?->toIso8601String(),
        ];

        // Extract userTeams from raceData to pass separately
        $userTeams = $raceData['userTeams'];
        unset($raceData['userTeams']);

        // Check if user is registered to this race (as team leader or participant)
        $alreadyRegistered = false;
        $registeredByLeader = null;
        $registeredTeam = null;
        if ($user) {
            // Check if user is registered to this race and get team details
            $registrationData = \DB::table('registration')
                ->join('teams', 'registration.equ_id', '=', 'teams.equ_id')
                ->join('has_participate', 'teams.equ_id', '=', 'has_participate.equ_id')
                ->where('registration.race_id', $race->race_id)
                ->where('has_participate.id_users', $user->id)
                ->select('teams.equ_id', 'teams.equ_name', 'teams.user_id', 'registration.reg_validated')
                ->first();

            if ($registrationData) {
                $alreadyRegistered = true;
                
                // Get team members
                $members = \DB::table('has_participate')
                    ->join('users', 'has_participate.id_users', '=', 'users.id')
                    ->leftJoin('members', 'users.adh_id', '=', 'members.adh_id')
                    ->where('has_participate.equ_id', $registrationData->equ_id)
                    ->select([
                        'users.id', 
                        'users.first_name', 
                        'users.last_name',
                        'users.birth_date',
                        'members.adh_license',
                        'members.adh_end_validity as license_expiry'
                    ])
                    ->get()
                    ->map(function($member) use ($registrationData, $race) {
                        $now = now();
                        $isLicenseValid = $member->license_expiry && $now->lessThan($member->license_expiry);
                        $age = $member->birth_date ? $now->diffInYears($member->birth_date) : null;
                        $isCompetitive = $race->type && strtolower($race->type->typ_name) === 'competitif';
                        
                        // Calculate price
                        if ($isLicenseValid && $race->price_adherent !== null) {
                            $price = $race->price_adherent;
                            $priceCategory = 'Adhérent';
                        } elseif (!$isCompetitive && $age !== null && $age < 18 && $race->price_minor !== null) {
                            $price = $race->price_minor;
                            $priceCategory = 'Mineur';
                        } else {
                            $price = $race->price_major ?? 0;
                            $priceCategory = 'Majeur';
                        }
                        
                        return [
                            'id' => $member->id,
                            'first_name' => $member->first_name,
                            'last_name' => $member->last_name,
                            'is_leader' => $member->id == $registrationData->user_id,
                            'price' => $price,
                            'price_category' => $priceCategory,
                        ];
                    });

                $registeredTeam = [
                    'id' => $registrationData->equ_id,
                    'name' => $registrationData->equ_name,
                    'members_count' => $members->count(),
                    'validated' => (bool) $registrationData->reg_validated,
                    'members' => $members->toArray(),
                    'total_price' => $members->sum('price'),
                ];

                // Check if user is registered as a runner but not as team leader
                if ($registrationData->user_id != $user->id) {
                    $leader = \DB::table('users')
                        ->where('id', $registrationData->user_id)
                        ->select('first_name', 'last_name')
                        ->first();
                    
                    if ($leader) {
                        $registeredByLeader = [
                            'leader_name' => trim($leader->first_name . ' ' . $leader->last_name),
                            'team_name' => $registrationData->equ_name,
                        ];
                    }
                }
            }
        }

        $raceData['alreadyRegistered'] = $alreadyRegistered;

        return Inertia::render('Race/VisuRace', [
            'race' => $raceData,
            'isManager' => $isRaceManager,
            'participants' => $participants,
            'userTeams' => $userTeams,
            'registeredByLeader' => $registeredByLeader,
            'registeredTeam' => $registeredTeam,
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
