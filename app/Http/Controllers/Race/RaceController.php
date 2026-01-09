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
        // Debug: Log all incoming request data
        \Log::info('Race store request data:', $request->all());
        
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
        
        \Log::info('Selected age categories:', ['categories' => $selectedCategories, 'count' => count($selectedCategories), 'type' => gettype($selectedCategories)]);
        
        if (!empty($selectedCategories)) {
            foreach ($selectedCategories as $ageCategorieId) {
                \Log::info('Creating param categorie age:', ['race_id' => $race->race_id, 'age_categorie_id' => $ageCategorieId]);
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
        ];

        // Update the race
        $race->update($raceData);

        // Assign responsable-course role to the new responsible if changed
        $responsibleUser = User::find($request->input('responsableId'));
        if ($responsibleUser) {
            $this->assignResponsableCourseRole($responsibleUser, $race);
        }

        return redirect()->route('races.show', $race->race_id)
            ->with('success', 'La course a été modifiée avec succès!');
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
}

