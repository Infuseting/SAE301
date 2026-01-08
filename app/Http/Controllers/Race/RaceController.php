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
        $race = Race::with(['runnerParams', 'teamParams'])->findOrFail($id);
        
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

        return Inertia::render('Race/NewRace', [    
            'users' => $users,
            'types' => $types,
            'ageCategories' => $ageCategories,
            'raid_id' => $raidId,
            'raid' => $raid,
            'race' => $race, // null for create, race data for edit
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
            'pae_nb_min' => $request->input('minTeams'),
            'pae_nb_max' => $request->input('maxTeams'),
            'pae_team_count_max' => $request->input('maxPerTeam'),
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
        if (!empty($selectedCategories)) {
            foreach ($selectedCategories as $ageCategorieId) {
                ParamCategorieAge::create([
                    'race_id' => $race->race_id,
                    'age_categorie_id' => $ageCategorieId,
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
                'pae_nb_min' => $request->input('minTeams'),
                'pae_nb_max' => $request->input('maxTeams'),
                'pae_team_count_max' => $request->input('maxPerTeam'),
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
}
