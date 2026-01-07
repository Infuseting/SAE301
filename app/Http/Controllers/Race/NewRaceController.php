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
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controller for managing race creation.
 */
class NewRaceController extends Controller
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
        // Load the raid if provided to check raid-specific permissions
        $raid = $request->query('raid_id') ? Raid::find($request->query('raid_id')) : null;
        
        // Authorize the user to create a race (pass raid for gestionnaire-raid check)
        $this->authorize('create', [Race::class, $raid]);
        
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
        
        // Authorize the user to update this race
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
        
        // Load the raid to get the club and filter users
        $raid = $raidId ? Raid::with('club')->find($raidId) : null;
        
        $usersQuery = User::select('id', 'last_name', 'first_name', 'email', 'adh_id')
            ->whereNotNull('adh_id'); // Only users with adherent ID can be responsable

        if ($raid && $raid->clu_id) {
            // Filter users who belong to the same club as the raid AND are approved
            $usersQuery->whereHas('clubs', function($q) use ($raid) {
                $q->where('clubs.club_id', $raid->clu_id)
                  ->where('club_user.status', 'approved');
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

        return Inertia::render('Race/NewRace', [    
            'users' => $users,
            'types' => $types,
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
            $path = $request->file('image')->store('races', 'public');
            $imageUrl = '/storage/' . $path;
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
            'price_adherent' => $request->input('priceMajorAdherent'), // Single adherent price for all
            'adh_id' => User::find($request->input('responsableId'))->adh_id,
            'race_difficulty' => $request->input('difficulty'),
            'typ_id' => $request->input('type'),
            'pac_id' => $paramRunner->pac_id,
            'pae_id' => $paramTeam->pae_id,
            'image_url' => $imageUrl,
            'cla_id' => $request->input('cla_id'),
            'raid_id' => $request->input('raid_id'),
        ];

        // Create the race
        $race = Race::create($raceData);

        return redirect()->route('races.show', $race->race_id)
            ->with('success', 'La course a été créée avec succès!');
    }

    /**
     * Update the specified race in storage.
     *
     * @param StoreRaceRequest $request
     * @param int $id
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

        // Update ParamRunner
        if ($race->pac_id) {
            ParamRunner::where('pac_id', $race->pac_id)->update([
                'pac_nb_min' => $request->input('minParticipants'),
                'pac_nb_max' => $request->input('maxParticipants'),
            ]);
        }

        // Update ParamTeam
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
            // Delete old image if exists
            if ($imageUrl && \Storage::disk('public')->exists(str_replace('/storage/', '', $imageUrl))) {
                \Storage::disk('public')->delete(str_replace('/storage/', '', $imageUrl));
            }
            
            $path = $request->file('image')->store('races', 'public');
            $imageUrl = '/storage/' . $path;
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
            'price_adherent' => $request->input('priceMajorAdherent'), // Single adherent price for all
            'adh_id' => User::find($request->input('responsableId'))->adh_id,
            'race_difficulty' => $request->input('difficulty'),
            'typ_id' => $request->input('type'),
            'image_url' => $imageUrl,
            'cla_id' => $request->input('cla_id'),
        ];

        // Update raid_id if provided (usually not changed but supported)
        if ($request->has('raid_id')) {
            $raceData['raid_id'] = $request->input('raid_id');
        }

        // Update the race
        $race->update($raceData);

        return redirect()->route('races.show', $race->race_id)
            ->with('success', 'La course a été mise à jour avec succès!');
    }

    /**
     * Remove the specified race from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id)
    {
        $race = Race::findOrFail($id);
        
        // Authorize deletion
        $this->authorize('delete', $race);

        // Delete associated params if they are not used elsewhere (optional cleanup)
        // For now, simpler to just delete the race
        
        $raidId = $race->raid_id;
        $race->delete();

        if ($raidId) {
            return redirect()->route('raids.show', $raidId)
                ->with('success', 'La course a été supprimée avec succès.');
        }

        return redirect()->route('raids.index')
            ->with('success', 'La course a été supprimée avec succès.');
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
