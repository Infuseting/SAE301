<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use Illuminate\Http\Request;
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
            // Get all active race registrations (both permanent and temporary teams)
            $registrations = \App\Models\RaceRegistration::where('race_id', $race->race_id)
                ->where('status', '!=', 'cancelled')
                ->with(['user', 'team.users'])
                ->get();

            $tempTeamCounter = 1;

            foreach ($registrations as $registration) {
                if ($registration->is_temporary_team) {
                    $teamName = "ÉQUIPE TEMPORAIRE " . $tempTeamCounter++;

                    // For temporary teams, get members from temporary_team_data
                    $teamData = $registration->temporary_team_data ?? [];

                    // Add creator if participating
                    if ($registration->is_creator_participating && $registration->user) {
                        $participants[] = $this->formatParticipant(
                            $registration->user,
                            $registration,
                            true, // is leader
                            $registration->user->hasValidCredentials() ? 'confirmed' : 'missing_credentials',
                            $teamName
                        );
                    }

                    // Add team members
                    foreach ($teamData as $member) {
                        if (isset($member['user_id'])) {
                            $memberUser = \App\Models\User::find($member['user_id']);
                            if ($memberUser) {
                                $status = ($member['status'] ?? 'pending') === 'accepted'
                                    ? ($memberUser->hasValidCredentials() ? 'confirmed' : 'missing_credentials')
                                    : 'invitation_pending';

                                $participants[] = $this->formatParticipant($memberUser, $registration, false, $status, $teamName);
                            }
                        } else {
                            // Invitation not yet accepted
                            $participants[] = [
                                'first_name' => $member['name'] ?? explode('@', $member['email'])[0],
                                'last_name' => '',
                                'email' => $member['email'],
                                'equ_name' => $teamName,
                                'status' => 'invitation_pending',
                                'is_license_valid' => false,
                                'is_pps_valid' => false,
                                'reg_validated' => false,
                            ];
                        }
                    }
                } else {
                    $teamName = $registration->team?->equ_name ?? 'Équipe permanente';

                    // For permanent teams, get members from team
                    if ($registration->team) {
                        foreach ($registration->team->users as $teamUser) {
                            $isLeader = (int) $teamUser->id === (int) $registration->user_id;
                            $status = $teamUser->hasValidCredentials() ? 'confirmed' : 'missing_credentials';

                            $participants[] = $this->formatParticipant($teamUser, $registration, $isLeader, $status, $teamName);
                        }
                    }
                }
            }
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
            // Find active registration where user is creator or member
            $registration = RaceRegistration::with(['user', 'team.leader', 'team.users'])
                ->where('race_id', $race->race_id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($user) {
                    $q->where('user_id', (int) $user->id)
                        ->orWhere('temporary_team_data', 'LIKE', '%"user_id":' . (int) $user->id . '%')
                        ->orWhere('temporary_team_data', 'LIKE', '%"user_id":"' . (int) $user->id . '"%')
                        ->orWhereHas('team.users', function ($q2) use ($user) {
                            $q2->where('users.id', (int) $user->id);
                        });
                })
                ->latest('reg_id')
                ->first();

            // If no active registration, check for a cancelled one (to preserve some state if needed, 
            // but we won't block re-registration)
            if (!$registration) {
                // If the user wants to re-register, we should probably NOT show their old cancelled registration
                // as the primary state, to allow the "Register" button to appear.
                // However, we'll keep it as a fallback only if we want to show history.
                // For now, let's keep it null if no active registration found.
            }

            if ($registration) {
                // User is leader if they are the creator OR if they are the leader of the permanent team
                $isLeader = (int) $registration->user_id === (int) $user->id ||
                    ($registration->team && (int) $registration->team->adh_id === (int) $user->id);

                $userRegistration = [
                    'id' => $registration->reg_id,
                    'status' => $registration->status,
                    'is_team_leader' => $isLeader,
                    'is_temporary_team' => $registration->is_temporary_team,
                    'is_creator_participating' => (bool) $registration->is_creator_participating,
                    'creator' => [
                        'name' => $registration->user?->name ?? 'Inconnu',
                        'email' => $registration->user?->email,
                    ],
                    'team_members' => $registration->getTeamMembers(),
                    'team' => $registration->team ? [
                        'id' => $registration->team->equ_id,
                        'name' => $registration->team->equ_name,
                    ] : null,
                    'created_at' => $registration->created_at?->toIso8601String(),
                    'can_edit' => $isLeader && $registration->canEdit(),
                    'is_team_complete' => $registration->isTeamComplete(),
                    'pending_invitations_count' => $registration->getPendingInvitationsCount(),
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
            ->where('status', '!=', 'cancelled')
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

    /**
     * Format participant data for display.
     *
     * @param User $user
     * @param RaceRegistration $registration
     * @param bool $isLeader
     * @param string $status
     * @return array
     */
    private function formatParticipant(User $user, RaceRegistration $registration, bool $isLeader, string $status, string $teamName): array
    {
        $member = $user->member;
        $medicalDoc = $user->medicalDoc;

        return [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'adh_license' => $member?->adh_license,
            'license_expiry' => $member?->adh_end_validity,
            'pps_number' => $medicalDoc?->doc_num_pps,
            'pps_expiry' => $medicalDoc?->doc_end_validity,
            'is_license_valid' => $member && $member->adh_end_validity && now()->lessThan($member->adh_end_validity),
            'is_pps_valid' => $medicalDoc && $medicalDoc->doc_end_validity && now()->lessThan($medicalDoc->doc_end_validity),
            'equ_name' => $teamName,
            'is_leader' => $isLeader,
            'status' => $status,
            'reg_validated' => $status === 'confirmed',
        ];
    }
}
