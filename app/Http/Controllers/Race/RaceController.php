<?php

namespace App\Http\Controllers\Race;

use App\Http\Controllers\Controller;
use App\Http\Requests\Race\StoreRaceRequest;
use App\Models\Race;
use App\Models\User;
use App\Models\ParamType;
use App\Models\ParamRunner;
use App\Models\ParamTeam;
use App\Models\Raid;
use App\Models\PriceAgeCategory;
use App\Models\AgeCategorie;
use App\Models\ParamCategorieAge;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller for managing race creation.
 */
class RaceController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the form for creating a new race.
     * Only responsable-course and admin can access this page.
     *
     * @return \Inertia\Response
     */
    public function show(Request $request)
    {
        // Authorize the user to create a race
        $this->authorize('create', Race::class);
        
        return $this->renderRaceForm($request);
    }

    /**
     * Show the form for editing an existing race.
     * Only the race organizer (adh_id matches) or admin can edit.
     *
     * @param int $id The race ID
     * @return \Inertia\Response
     */
    public function edit(int $id)
    {
        $race = Race::with(['runnerParams', 'teamParams', 'categorieAges.ageCategory'])->findOrFail($id);
        
        // Authorize the user to update this race (checks ownership)
        $this->authorize('update', $race);
        
        return $this->renderRaceForm(request(), $race);
    }

    /**
     * Render the race form (used for both create and edit)
     *
     * @param Request $request
     * @param Race|null $race The race to edit (null for create)
     * @return \Inertia\Response
     */
    /**
     * Render the race form (used for both create and edit)
     *
     * @param Request $request
     * @param Race|null $race The race to edit (null for create)
     * @return \Inertia\Response
     */
    private function renderRaceForm(Request $request, ?Race $race = null)
    {
        $raidId = $race ? $race->raid_id : $request->query('raid_id');
        $raid = $raidId ? Raid::find($raidId) : null;
        $usersQuery = User::select('id', 'last_name', 'first_name', 'email', 'adh_id');

        if ($raid) {
            // Filter users who belong to the same club as the raid
            $usersQuery->whereHas('clubs', function($q) use ($raid) {
                $q->where('clubs.club_id', $raid->clu_id);
            });
        }

        // Get all users for responsable selection (filtered by raid club if applicable)
        $users = $usersQuery->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'adh_id' => $user->adh_id,
            ])
            ->toArray();

        // Get all types from database
        $types = ParamType::select('typ_id', 'typ_name')
            ->orderBy('typ_id')
            ->get()
            ->map(fn($type) => [
                'id' => $type->typ_id,
                'name' => $type->typ_name,
            ])
            ->toArray();

        // Get all age categories
        $ageCategories = AgeCategorie::select('id', 'nom', 'age_min', 'age_max')
            ->orderBy('age_min')
            ->get()
            ->toArray();

        // Transform race data for edit mode
        $raceData = $race ? [
            'race_id' => $race->race_id,
            'race_name' => $race->race_name,
            'race_description' => $race->race_description,
            'race_date_start' => $race->race_date_start,
            'race_date_end' => $race->race_date_end,
            'race_duration_minutes' => $race->race_duration_minutes,
            'race_meal_price' => $race->race_meal_price,
            'price_major' => $race->price_major,
            'price_minor' => $race->price_minor,
            'price_adherent' => $race->price_adherent,
            'race_difficulty' => $race->race_difficulty,
            'typ_id' => $race->typ_id,
            'adh_id' => $race->adh_id,
            'raid_id' => $race->raid_id,
            // Leisure age rules (A <= B <= C)
            'leisure_age_min' => $race->leisure_age_min,
            'leisure_age_intermediate' => $race->leisure_age_intermediate,
            'leisure_age_supervisor' => $race->leisure_age_supervisor,
            'runner_params' => $race->runnerParams,
            'team_params' => $race->teamParams,
            'categorieAges' => $race->categorieAges->map(fn($pc) => [
                'id' => $pc->id,
                'race_id' => $pc->race_id,
                'age_categorie_id' => $pc->age_categorie_id,
                'ageCategory' => [
                    'id' => $pc->ageCategory->id,
                    'nom' => $pc->ageCategory->nom,
                    'age_min' => $pc->ageCategory->age_min,
                    'age_max' => $pc->ageCategory->age_max,
                ]
            ])->toArray()
        ] : null;

        return Inertia::render('Race/NewRace', [    
            'users' => $users,
            'types' => $types,
            'ageCategories' => $ageCategories,
            'raid_id' => $raidId,
            'raid' => $raid,
            'race' => $raceData,
            'auth' => [
                'user' => Auth::user(),
            ],
        ]);
    }

    /**
     * Store a newly created race in the database.
     *
     * @param StoreRaceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRaceRequest $request)
    {
        
        $raid = $request->input('raid_id') ? Raid::find($request->input('raid_id')) : null;

        // Authorize the user to create a race for this raid
        $this->authorize('create', [Race::class, $raid]);

        // Combine date and time fields
        $startDateTime = $request->input('startDate') . ' ' . $request->input('startTime');
        $endDateTime = $request->input('endDate') . ' ' . $request->input('endTime');

        // Create ParamRunner entry for this race
        $paramRunner = ParamRunner::create([
            'pac_nb_min' => $request->input('minParticipants'),
            'pac_nb_max' => $request->input('maxParticipants'),
        ]);

        // Create ParamTeam entry for this race
        $paramTeam = ParamTeam::create([
            'pae_nb_min' => $request->input('minTeams') ?: 1,
            'pae_nb_max' => $request->input('maxTeams') ?: 1,
            'pae_team_count_min' => $request->input('minPerTeam') ?: 1,
            'pae_team_count_max' => $request->input('maxPerTeam') ?: 1,
        ]);

        // Handle image upload
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('races', 'public');
        }

        // Prepare race data
        $raceData = [
            'race_name' => $request->input('title'),
            'race_description' => $request->input('description'),
            'race_date_start' => $startDateTime,
            'race_date_end' => $endDateTime,
            'race_duration_minutes' => $this->convertDurationToMinutes($request->input('duration')),
            'race_meal_price' => $request->input('mealPrice'),
            'price_major' => $request->input('priceMajor'),
            'price_minor' => $request->input('priceMinor'),
            'price_adherent' => $request->input('priceAdherent'),
            'adh_id' => User::find($request->input('responsableId'))->adh_id,
            'race_difficulty' => $request->input('difficulty'),
            'typ_id' => $request->input('type'),
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'image_url' => $imageUrl,
            'raid_id' => $request->input('raid_id'),
            // Leisure age rules (A <= B <= C)
            'leisure_age_min' => $request->input('leisureAgeMin'),
            'leisure_age_intermediate' => $request->input('leisureAgeIntermediate'),
            'leisure_age_supervisor' => $request->input('leisureAgeSupervisor'),
        ];

        // Create the race
        $race = Race::create($raceData);

        // Insert selected age categories
        $selectedCategories = $request->input('selectedAgeCategories', []);
        
        // Handle both array and JSON formats
        if (is_string($selectedCategories)) {
            $selectedCategories = json_decode($selectedCategories, true) ?? [];
        }
        if (!is_array($selectedCategories)) {
            $selectedCategories = [];
        }

        if (!empty($selectedCategories)) {
            foreach ($selectedCategories as $ageCategorieId) {
                ParamCategorieAge::create([
                    'race_id' => $race->race_id,
                    'age_categorie_id' => (int)$ageCategorieId,
                ]);
            }
        }

        // Assign responsable-course role to the designated responsible
        $responsibleUser = User::find($request->input('responsableId'));
        if ($responsibleUser) {
            $this->assignResponsableCourseRole($responsibleUser, $race);
        }

        return redirect()->route('races.show', $race->race_id)
            ->with('success', 'La course a été créée avec succès!');
    }

    /**
     * Update an existing race in the database.
     *
     * @param StoreRaceRequest $request
     * @param int $id The race ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(StoreRaceRequest $request, int $id)
    {
        $race = Race::findOrFail($id);
        
        // Authorize the user to update this race
        $this->authorize('update', $race);

        // Combine date and time fields
        $startDateTime = $request->input('startDate') . ' ' . $request->input('startTime');
        $endDateTime = $request->input('endDate') . ' ' . $request->input('endTime');

        // Update ParamRunner entry if exists
        if ($race->pac_id) {
            ParamRunner::where('pac_id', $race->pac_id)->update([
                'pac_nb_min' => $request->input('minParticipants'),
                'pac_nb_max' => $request->input('maxParticipants'),
            ]);
        }

        // Update ParamTeam entry if exists
        if ($race->pae_id) {
            ParamTeam::where('pae_id', $race->pae_id)->update([
                'pae_nb_min' => $request->input('minTeams') ?: 1,
                'pae_nb_max' => $request->input('maxTeams') ?: 1,
                'pae_team_count_min' => $request->input('minPerTeam') ?: 1,
                'pae_team_count_max' => $request->input('maxPerTeam') ?: 1,
            ]);
        }

        // Handle image upload
        $imageUrl = $race->image_url;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('races', 'public');
        }

        // Prepare race data for update
        $raceData = [
            'race_name' => $request->input('title'),
            'race_description' => $request->input('description'),
            'race_date_start' => $startDateTime,
            'race_date_end' => $endDateTime,
            'race_duration_minutes' => $this->convertDurationToMinutes($request->input('duration')),
            'race_meal_price' => $request->input('mealPrice'),
            'price_major' => $request->input('priceMajor'),
            'price_minor' => $request->input('priceMinor'),
            'price_adherent' => $request->input('priceAdherent'),
            'adh_id' => User::find($request->input('responsableId'))->adh_id,
            'race_difficulty' => $request->input('difficulty'),
            'typ_id' => $request->input('type'),
            'image_url' => $imageUrl,
            // Leisure age rules (A <= B <= C)
            'leisure_age_min' => $request->input('leisureAgeMin'),
            'leisure_age_intermediate' => $request->input('leisureAgeIntermediate'),
            'leisure_age_supervisor' => $request->input('leisureAgeSupervisor'),
        ];

        // Update the race
        $race->update($raceData);

        // Handle age categories for competitive races
        $typeId = $request->input('type');
        $type = ParamType::find($typeId);
        $isCompetitive = $type && strtolower($type->typ_name) === 'compétitif';

        // Update age categories (for competitive races)
        $ageCategories = $request->input('selectedAgeCategories', []);
        
        // Handle both array and JSON formats
        if (is_string($ageCategories)) {
            $ageCategories = json_decode($ageCategories, true) ?? [];
        }
        if (!is_array($ageCategories)) {
            $ageCategories = [];
        }
        
        ParamCategorieAge::where('race_id', $race->race_id)->delete();
        foreach ($ageCategories as $categoryId) {
            ParamCategorieAge::create([
                'race_id' => $race->race_id,
                'age_categorie_id' => (int) $categoryId,
            ]);
        }

        // Kick teams that no longer comply with the new age rules
        $kickedTeamsCount = $this->kickNonCompliantTeams($race, $isCompetitive, $ageCategories, $request);

        // Assign responsable-course role to the new responsible if changed
        $responsibleUser = User::find($request->input('responsableId'));
        if ($responsibleUser) {
            $this->assignResponsableCourseRole($responsibleUser, $race);
        }

        $successMessage = 'La course a été modifiée avec succès!';
        if ($kickedTeamsCount > 0) {
            $successMessage .= " {$kickedTeamsCount} équipe(s) ont été retirées car elles ne respectent plus les règles d'âge.";
        }

        return redirect()->route('races.show', $race->race_id)
            ->with('success', $successMessage);
    }

    /**
     * Kick teams that no longer comply with age rules after a race update.
     *
     * @param Race $race The race being updated
     * @param bool $isCompetitive Whether the race is competitive
     * @param array $ageCategories Array of accepted age category IDs (for competitive)
     * @param Request $request The request with leisure age rules
     * @return int Number of teams kicked
     */
    private function kickNonCompliantTeams(Race $race, bool $isCompetitive, array $ageCategories, Request $request): int
    {
        $kickedCount = 0;
        // Get all teams registered for this race (no eager loading needed, we query members separately)
        $registeredTeams = $race->teams()->get();

        foreach ($registeredTeams as $team) {
            $isCompliant = $this->validateTeamAgeCompliance(
                $team,
                $isCompetitive,
                $ageCategories,
                $request->input('leisureAgeMin'),
                $request->input('leisureAgeIntermediate'),
                $request->input('leisureAgeSupervisor')
            );

            if (!$isCompliant) {
                // Remove team from race registration
                \DB::table('registration')
                    ->where('race_id', $race->race_id)
                    ->where('equ_id', $team->equ_id)
                    ->delete();

                // Also remove from race_participants table
                \DB::table('race_participants')
                    ->whereIn('reg_id', function($query) use ($race, $team) {
                        $query->select('reg_id')
                            ->from('registration')
                            ->where('race_id', $race->race_id)
                            ->where('equ_id', $team->equ_id);
                    })
                    ->delete();

                // Log the removal
                activity()
                    ->performedOn($race)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'team_id' => $team->equ_id,
                        'team_name' => $team->equ_name,
                        'reason' => 'Age rules changed - team no longer compliant'
                    ])
                    ->log('Team removed from race due to age rule changes');

                $kickedCount++;
            }
        }

        return $kickedCount;
    }

    /**
     * Validate if a team complies with age rules.
     *
     * @param \App\Models\Team $team The team to validate
     * @param bool $isCompetitive Whether the race is competitive
     * @param array $ageCategories Accepted age category IDs (for competitive)
     * @param int|null $leisureAgeMin Age A - minimum age for all (leisure)
     * @param int|null $leisureAgeIntermediate Age B - intermediate threshold (leisure)
     * @param int|null $leisureAgeSupervisor Age C - supervisor age (leisure)
     * @return bool True if team is compliant
     */
    private function validateTeamAgeCompliance(
        $team,
        bool $isCompetitive,
        array $ageCategories,
        ?int $leisureAgeMin,
        ?int $leisureAgeIntermediate,
        ?int $leisureAgeSupervisor
    ): bool {
        // Get team members with their ages
        $members = \DB::table('has_participate')
            ->join('users', 'has_participate.id_users', '=', 'users.id')
            ->where('has_participate.equ_id', $team->equ_id)
            ->select('users.id', 'users.birth_date')
            ->get();

        if ($members->isEmpty()) {
            return false;
        }

        $now = now();
        $memberAges = $members->map(function($m) use ($now) {
            if (!$m->birth_date) {
                return null;
            }
            $birthDate = \Carbon\Carbon::parse($m->birth_date);
            return (int) $birthDate->diffInYears($now);
        })->filter()->values()->toArray();

        if (empty($memberAges)) {
            return false; // No valid ages means non-compliant
        }

        if ($isCompetitive) {
            return $this->validateCompetitiveTeam($memberAges, $ageCategories);
        } else {
            return $this->validateLeisureTeam($memberAges, $leisureAgeMin, $leisureAgeIntermediate, $leisureAgeSupervisor);
        }
    }

    /**
     * Validate a team for competitive race age rules.
     * All members must be in the same accepted age category.
     *
     * @param array $memberAges Array of member ages
     * @param array $ageCategoryIds Array of accepted age category IDs
     * @return bool True if team is compliant
     */
    private function validateCompetitiveTeam(array $memberAges, array $ageCategoryIds): bool
    {
        if (empty($ageCategoryIds)) {
            return true; // No categories defined = no restrictions
        }

        // Get the age categories
        $ageCategories = AgeCategorie::whereIn('id', $ageCategoryIds)->get();

        if ($ageCategories->isEmpty()) {
            return true; // No valid categories = no restrictions
        }

        // Determine each member's category
        $memberCategories = [];
        foreach ($memberAges as $age) {
            $category = $ageCategories->first(function($cat) use ($age) {
                $minAge = $cat->age_min;
                $maxAge = $cat->age_max !== null ? $cat->age_max : PHP_INT_MAX;
                return $age >= $minAge && $age <= $maxAge;
            });

            if (!$category) {
                return false; // Member age not in any accepted category
            }

            $memberCategories[] = $category->id;
        }

        // Check all members are in the same category
        $uniqueCategories = array_unique($memberCategories);
        return count($uniqueCategories) === 1;
    }

    /**
     * Validate a team for leisure race age rules.
     * Rules: A <= B <= C
     * - All participants must be at least A years old
     * - If any participant is under B, team must have someone at least C
     * - OR all participants must be at least B years old
     *
     * @param array $memberAges Array of member ages
     * @param int|null $ageA Minimum age for all
     * @param int|null $ageB Intermediate threshold
     * @param int|null $ageC Supervisor age requirement
     * @return bool True if team is compliant
     */
    private function validateLeisureTeam(array $memberAges, ?int $ageA, ?int $ageB, ?int $ageC): bool
    {
        // If no leisure rules defined, allow all
        if ($ageA === null || $ageB === null || $ageC === null) {
            return true;
        }

        // Rule 1: All participants must be at least A years old
        foreach ($memberAges as $age) {
            if ($age < $ageA) {
                return false;
            }
        }

        // Check if any member is under B years old
        $membersBelowB = array_filter($memberAges, fn($age) => $age < $ageB);
        $needsSupervisor = count($membersBelowB) > 0;

        // Check if there's a supervisor (someone at least C years old)
        $supervisors = array_filter($memberAges, fn($age) => $age >= $ageC);
        $hasSupervisor = count($supervisors) > 0;

        // Rule 2: If any participant is under B, team must have someone at least C
        if ($needsSupervisor && !$hasSupervisor) {
            return false;
        }

        return true;
    }

    /**
     * Assign responsable-course role to a user for a specific race.
     * The user must be an adherent and a member of the raid's club.
     *
     * @param User $user
     * @param Race $race
     * @return void
     */
    protected function assignResponsableCourseRole(User $user, Race $race): void
    {
        // Check if the user has an adherent ID
        if (!$user->adh_id) {
            return;
        }

        // Get the raid to check club membership
        $raid = $race->raid;
        if (!$raid) {
            return;
        }

        // Check if the user is a member of the raid's club
        $isMemberOfClub = $user->clubs()
            ->where('clubs.club_id', $raid->clu_id)
            ->wherePivot('status', 'approved')
            ->exists();

        if (!$isMemberOfClub) {
            return;
        }

        // Assign responsable-course role (even if user has other roles)
        if (!$user->hasRole('responsable-course')) {
            $user->assignRole('responsable-course');
            
            activity()
                ->performedOn($race)
                ->causedBy(auth()->user())
                ->withProperties(['user' => $user->first_name . ' ' . $user->last_name])
                ->log('User assigned as race manager');
        }
    }

    /**
     * Delete the specified race.
     *
     * @param int $id The race ID to delete
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id)
    {
        $race = Race::findOrFail($id);
        
        // Authorize the user to delete the race
        $this->authorize('delete', $race);
        
        $raidId = $race->raid_id;
        
        // Delete associated ParamRunner if exists
        if ($race->pac_id) {
            ParamRunner::where('pac_id', $race->pac_id)->delete();
        }
        
        // Delete associated ParamTeam if exists
        if ($race->pae_id) {
            ParamTeam::where('pae_id', $race->pae_id)->delete();
        }
        
        // Delete the race
        $race->delete();
        
        return redirect()->route('raids.show', $raidId)
            ->with('success', 'La course a été supprimée avec succès!');
    }

    /**
     * Convert duration string (H:mm) to minutes.
     *
     * @param string|null $duration
     * @return int|null
     */
    private function convertDurationToMinutes(?string $duration): ?int
    {
        if (!$duration) {
            return null;
        }

        try {
            [$hours, $minutes] = explode(':', $duration);
            return (int)$hours * 60 + (int)$minutes;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate PDF start-list for race
     * 
     * @OA\Get(
     *     path="/races/{race}/start-list",
     *     tags={"Races"},
     *     summary="Generate PDF start-list",
     *     description="Download PDF with all validated race registrations",
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         description="Race ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF file",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Must be race manager"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Race not found"
     *     )
     * )
     */
    public function generateStartList(Race $race)
    {
        // Check if user is race manager or admin
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('admin');
        $isRaceManager = $user && ($user->hasRole('responsable-course') || $race->adh_id === $user->adh_id);

        if (!$isAdmin && !$isRaceManager) {
            abort(403, 'Unauthorized. Only race managers can download the start-list.');
        }

        // Get all validated registrations for this race
        $registrations = \DB::table('registration')
            ->where('race_id', $race->race_id)
            ->where('reg_validated', true)
            ->orderBy('reg_dossard')
            ->get();

        // Load teams and captains for each registration
        $teams = $registrations->map(function ($registration) {
            $team = \App\Models\Team::with('leader')->find($registration->equ_id);
            $registration->team = $team;
            return $registration;
        });

        // Generate PDF
        $pdf = \PDF::loadView('pdf.race-start-list', [
            'race' => $race,
            'registrations' => $teams,
            'totalTeams' => count($teams),
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Download the PDF
        return $pdf->download('start-list-' . \Str::slug($race->race_name) . '.pdf');
    }

    /**
     * Mark team as present by scanning QR code
     * 
     * @OA\Post(
     *     path="/races/{race}/check-in",
     *     tags={"Races"},
     *     summary="Mark team as present",
     *     description="Scan QR code and mark team registration as present",
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         description="Race ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"equ_id", "reg_id"},
     *             @OA\Property(property="equ_id", type="integer", description="Team ID"),
     *             @OA\Property(property="reg_id", type="integer", description="Registration ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Team marked as present",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="registration", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Must be race manager"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Registration not found"
     *     )
     * )
     */
    public function checkIn(Request $request, Race $race)
    {
        // Check if user is the race manager (owner of the race)
        $user = auth()->user();
        
        // Get the raid to check club ownership
        $raid = $race->raid()->with('club')->first();
        $isRaceManager = $user && $raid && $raid->club && ($raid->club->created_by === $user->id);

        if (!$isRaceManager) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the race manager can check-in teams.'
            ], 403);
        }

        $validated = $request->validate([
            'equ_id' => 'required|integer|exists:teams,equ_id',
            'reg_id' => 'required|integer|exists:registration,reg_id',
        ]);

        // Find the registration
        $registration = \App\Models\Registration::with(['team.leader', 'race'])
            ->where('reg_id', $validated['reg_id'])
            ->where('equ_id', $validated['equ_id'])
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found.'
            ], 404);
        }

        // Check if registration belongs to this race
        if ($registration->race_id !== $race->race_id) {
            return response()->json([
                'success' => false,
                'message' => 'This registration does not belong to this race.'
            ], 400);
        }

        // Check if already present
        if ($registration->is_present) {
            return response()->json([
                'success' => true,
                'message' => 'Team already checked in.',
                'already_present' => true,
                'registration' => [
                    'reg_id' => $registration->reg_id,
                    'reg_dossard' => $registration->reg_dossard,
                    'team_name' => $registration->team->equ_name,
                    'race_name' => $registration->race->race_name,
                    'is_present' => $registration->is_present,
                ]
            ]);
        }

        // Mark as present
        $registration->is_present = true;
        $registration->save();

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($registration)
            ->log('Team checked in at race');

        return response()->json([
            'success' => true,
            'message' => 'Team successfully checked in!',
            'registration' => [
                'reg_id' => $registration->reg_id,
                'reg_dossard' => $registration->reg_dossard,
                'team_name' => $registration->team->equ_name,
                'race_name' => $registration->race->race_name,
                'leader_name' => $registration->team->leader ? 
                    $registration->team->leader->first_name . ' ' . $registration->team->leader->last_name : 
                    'N/A',
                'is_present' => $registration->is_present,
            ]
        ]);
    }

    /**
     * Toggle participant presence status
     * 
     * @OA\Post(
     *     path="/races/{race}/toggle-presence",
     *     summary="Toggle participant presence",
     *     tags={"Races"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reg_id"},
     *             @OA\Property(property="reg_id", type="integer", description="Registration ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Presence toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="is_present", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Must be race manager"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Registration not found"
     *     )
     * )
     */
    public function togglePresence(Request $request, Race $race)
    {
        // Check if user is race manager or admin
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('admin');
        $isRaceManager = $user && ($user->hasRole('responsable-course') || ($race->organizer && $user->adh_id === $race->organizer->adh_id) || ($race->raid && $race->raid->club && $race->raid->club->hasManager($user)));

        if (!$isAdmin && !$isRaceManager) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only race managers can toggle presence.'
            ], 403);
        }

        $validated = $request->validate([
            'reg_id' => 'required|integer|exists:registration,reg_id',
        ]);

        // Find the registration
        $registration = \App\Models\Registration::where('reg_id', $validated['reg_id'])
            ->where('race_id', $race->race_id)
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found.'
            ], 404);
        }

        // Toggle presence
        $registration->is_present = !$registration->is_present;
        $registration->save();

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($registration)
            ->log($registration->is_present ? 'Participant marked as present' : 'Participant marked as absent');

        return response()->json([
            'success' => true,
            'is_present' => $registration->is_present,
            'message' => $registration->is_present ? 'Participant marqué comme présent' : 'Participant marqué comme absent'
        ]);
    }

    /**
     * Display QR scanner page for race managers
     */
    public function scannerPage(Race $race)
    {
        // Check if user is race manager or admin
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('admin');
        $isRaceManager = $user && ($user->hasRole('responsable-course') || $race->adh_id === $user->adh_id);

        if (!$isAdmin && !$isRaceManager) {
            abort(403, 'Unauthorized. Only race managers can access the scanner.');
        }

        // Get statistics
        $totalRegistrations = \DB::table('registration')
            ->where('race_id', $race->race_id)
            ->where('reg_validated', true)
            ->count();

        $presentCount = \DB::table('registration')
            ->where('race_id', $race->race_id)
            ->where('reg_validated', true)
            ->where('is_present', true)
            ->count();

        return Inertia::render('Race/Scanner', [
            'race' => [
                'race_id' => $race->race_id,
                'race_name' => $race->race_name,
                'race_date' => $race->race_date,
            ],
            'stats' => [
                'total' => $totalRegistrations,
                'present' => $presentCount,
                'absent' => $totalRegistrations - $presentCount,
            ],
        ]);
    }

    /**
     * Get team members for a registration (used after QR scan)
     * 
     * @OA\Get(
     *     path="/races/{race}/team-members/{registration}",
     *     tags={"Races"},
     *     summary="Get team members for registration",
     *     description="Retrieves all team members with their registration status for a given registration",
     *     @OA\Parameter(
     *         name="race",
     *         in="path",
     *         description="Race ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="registration",
     *         in="path",
     *         description="Registration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Team members retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="team", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Must be race manager"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Registration not found"
     *     )
     * )
     */
    public function getTeamMembers(Race $race, int $registration)
    {
        // Check if user is race manager or admin
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('admin');
        $isRaceManager = $user && ($user->hasRole('responsable-course') || ($race->organizer && $user->adh_id === $race->organizer->adh_id) || ($race->raid && $race->raid->club && $race->raid->club->hasManager($user)));

        if (!$isAdmin && !$isRaceManager) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only race managers can access team members.'
            ], 403);
        }

        // Find the registration
        $registrationData = \App\Models\Registration::with(['team.leader'])
            ->where('reg_id', $registration)
            ->where('race_id', $race->race_id)
            ->first();

        if (!$registrationData) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found.'
            ], 404);
        }

        // Get team members with their status
        $members = \DB::table('race_participants')
            ->join('registration', 'race_participants.reg_id', '=', 'registration.reg_id')
            ->join('users', 'race_participants.user_id', '=', 'users.id')
            ->leftJoin('members', 'users.adh_id', '=', 'members.adh_id')
            ->where('race_participants.reg_id', $registration)
            ->select([
                'race_participants.rpa_id as participant_id',
                'race_participants.reg_id',
                'users.id as user_id',
                'users.id as id_users',
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
                'registration.reg_dossard',
                'registration.is_present',
            ])
            ->get()
            ->map(function($p) use ($race, $registrationData) {
                $now = now();
                $p->is_license_valid = $p->license_expiry && $now->lessThan($p->license_expiry);
                $p->is_pps_valid = $p->pps_expiry && 
                                   $now->lessThan($p->pps_expiry) && 
                                   $p->pps_status === 'verified' &&
                                   !str_starts_with($p->pps_number ?? '', 'PENDING-');
                $p->is_captain = $registrationData->team && $registrationData->team->user_id === $p->user_id;
                
                // Calculate participant price
                $age = $p->birth_date ? $now->diffInYears($p->birth_date) : null;
                $isCompetitive = $race->type && strtolower($race->type->typ_name) === 'compétitif';
                
                if ($p->is_license_valid && $race->price_adherent !== null) {
                    $p->price = $race->price_adherent;
                    $p->price_category = 'Adhérent';
                } elseif (!$isCompetitive && $age !== null && $age < 18 && $race->price_minor !== null) {
                    $p->price = $race->price_minor;
                    $p->price_category = 'Mineur';
                } else {
                    $p->price = $race->price_major ?? 0;
                    $p->price_category = 'Majeur';
                }
                
                return $p;
            });

        return response()->json([
            'success' => true,
            'team' => [
                'id' => $registrationData->team->equ_id,
                'name' => $registrationData->team->equ_name,
                'dossard' => $registrationData->reg_dossard,
                'reg_id' => $registrationData->reg_id,
                'reg_validated' => $registrationData->reg_validated,
                'is_present' => $registrationData->is_present,
                'members' => $members,
            ]
        ]);
    }
}

