<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Inertia\Inertia;

/**
 * Controller for displaying race details
 */
class VisuRaceController extends Controller
{
    /**
     * Display the specified race.
     *
     * @param int $id
     * @return \Inertia\Response
     */
    public function show(int $id)
    {
        // Find the race by ID
        $race = Race::with(['organizer.user', 'raid.registrationPeriod', 'type', 'teamParams'])->find($id);

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
                ->map(function ($p) {
                    $now = now();
                    $p->is_license_valid = $p->license_expiry && $now->lessThan($p->license_expiry);
                    $p->is_pps_valid = $p->pps_expiry && $now->lessThan($p->pps_expiry);
                    return $p;
                });
        }

        // Transform data for frontend
        $raceData = [
            'id' => $race->race_id,
            'title' => $race->race_name,
            'description' => $race->raid?->raid_description ?? 'Aucune description disponible.',
            'location' => $race->raid?->raid_location ?? 'Lieu à définir',
            'latitude' => $race->raid?->raid_latitude ?? 48.8566,
            'longitude' => $race->raid?->raid_longitude ?? 2.3522,
            'raceDate' => $race->race_date_start?->toIso8601String(),
            'endDate' => $race->race_date_end?->toIso8601String(),
            'duration' => $race->race_duration_minutes ? floor($race->race_duration_minutes / 60) . ':' . str_pad((int) ($race->race_duration_minutes % 60), 2, '0', STR_PAD_LEFT) : '0:00',
            'raceType' => $race->type?->typ_name ?? 'Classique',
            'difficulty' => $race->race_difficulty ?? 'Moyen',
            'status' => $this->getRaceStatus($race),
            'isOpen' => $race->isOpen(),
            'registrationUpcoming' => $race->isRegistrationUpcoming(),
            'imageUrl' => $race->image_url ? asset('storage/' . $race->image_url) : null,
            'description' => $race->race_description ?? 'Aucune description disponible.',
            'maxParticipants' => 100,
            'registeredCount' => $this->getParticipantCount($race),
            'organizer' => [
                'id' => $race->organizer?->user?->id,
                'name' => trim(($race->organizer?->adh_firstname ?? '') . ' ' . ($race->organizer?->adh_lastname ?? '')) ?: ($race->organizer?->user?->name ?? 'Organisateur'),
                'email' => $race->organizer?->user?->email ?? ''
            ],
            'categories' => [],
            'priceMajor' => $race->price_major,
            'priceMinor' => $race->price_minor,
            'priceMajorAdherent' => $race->price_adherent,
            'priceMinorAdherent' => $race->price_adherent, // Use same price for frontend consistency
            'minTeams' => $race->teamParams?->pae_nb_min ?? 1,
            'maxTeams' => $race->teamParams?->pae_nb_max ?? 1,
            'maxPerTeam' => $race->teamParams?->pae_team_count_max ?? 1,
            'createdAt' => $race->created_at?->toIso8601String(),
            'updatedAt' => $race->updated_at?->toIso8601String(),
        ];

        // Check if current user is registered for this race
        $userRegistration = null;
        if ($user) {
            $registration = \App\Models\RaceRegistration::where('race_id', $race->race_id)
                ->where('user_id', $user->id)
                ->first();

            if ($registration) {
                $userRegistration = [
                    'id' => $registration->reg_id,
                    'status' => $registration->status,
                    'is_team_leader' => $registration->is_team_leader,
                    'is_temporary_team' => $registration->is_temporary_team,
                    'is_creator_participating' => $registration->is_creator_participating,
                    'team_members' => $registration->getTeamMembers(),
                    'team' => $registration->team ? [
                        'id' => $registration->team->equ_id,
                        'name' => $registration->team->equ_name,
                    ] : null,
                    'created_at' => $registration->created_at?->toIso8601String(),
                ];
            }
        }

        return Inertia::render('Race/VisuRace', [
            'race' => $raceData,
            'isManager' => $isRaceManager,
            'participants' => $participants,
            'userRegistration' => $userRegistration,
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

    /**
     * Count total participants from race registrations.
     * Counts team members from both permanent and temporary teams.
     *
     * @param Race $race
     * @return int
     */
    private function getParticipantCount(Race $race): int
    {
        $registrations = \App\Models\RaceRegistration::where('race_id', $race->race_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        $count = 0;

        foreach ($registrations as $registration) {
            if ($registration->usesTemporaryTeam()) {
                // Temporary team: count creator + temporary members
                if ($registration->is_creator_participating) {
                    $count++;
                }
                $count += count($registration->temporary_team_data ?? []);
            } else {
                // Permanent team: just count team members (creator is included)
                $members = $registration->getTeamMembers();
                $count += count($members);
            }
        }

        return $count;
    }
}
