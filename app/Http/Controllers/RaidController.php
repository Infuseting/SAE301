<?php

namespace App\Http\Controllers;

use App\Models\Raid;
use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRaidRequest;
use App\Http\Requests\UpdateRaidRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Annotations as OA;

class RaidController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     * Returns all raids for client-side filtering and search.
     * 
     * @OA\Get(
     *     path="/raids",
     *     tags={"Raids"},
     *     summary="Get list of raids",
     *     description="Returns all raids with related data for client-side filtering",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="raids",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Raid")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): Response
    {
        // Get all raids with related data for client-side filtering
        $raids = Raid::query()
            ->with(['club:club_id,club_name', 'registrationPeriod:ins_id,ins_start_date,ins_end_date'])
            ->withCount('races')
            ->orderBy('raid_date_start', 'desc')
            ->get();
        
        return Inertia::render('Raid/List', [
            'raids' => $raids,
            'filters' => [
                'q' => $request->input('q', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * Automatically loads the user's club and its members
     */
    public function create(): Response
    {
        // Get the club created by the current user
        $userClub = \DB::table('clubs')
            ->where('created_by', auth()->id())
            ->first(['club_id', 'club_name']);

        // Log current user info
        \Log::info('User connecté:', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'user_adh_id' => auth()->user()->adh_id,
            'club_found' => $userClub ? $userClub->club_name : 'Aucun club'
        ]);

        // Get members (adherents) of this club from club_user table
        $clubMembers = [];
        if ($userClub) {
            $clubMembers = \DB::table('club_user')
                ->join('users', 'club_user.user_id', '=', 'users.id')
                ->where('club_user.club_id', $userClub->club_id)
                ->whereNotNull('users.adh_id') // Ensure they have an adherent ID
                ->select('users.adh_id', 'users.first_name', 'users.last_name')
                ->get()
                ->map(function ($user) {
                    return [
                        'adh_id' => $user->adh_id,
                        'full_name' => $user->first_name . ' ' . $user->last_name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                    ];
                });

            // Log club members
            \Log::info('Liste des responsables possibles:', [
                'club_id' => $userClub->club_id,
                'club_name' => $userClub->club_name,
                'members_count' => $clubMembers->count(),
                'members' => $clubMembers->toArray()
            ]);
        }

        return Inertia::render('Raid/Create', [
            'userClub' => $userClub,
            'clubMembers' => $clubMembers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Only responsable-club can create raids.
     * 
     * @OA\Post(
     *     path="/raids",
     *     tags={"Raids"},
     *     summary="Create a new raid",
     *     description="Creates a new raid event. Only responsable-club can create raids.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"raid_name", "clu_id", "raid_date_start", "raid_date_end", "ins_start_date", "ins_end_date", "raid_contact", "raid_street", "raid_city", "raid_postal_code", "raid_number"},
     *             @OA\Property(property="raid_name", type="string", example="Mountain Adventure Raid"),
     *             @OA\Property(property="clu_id", type="integer", example=1, description="Club ID"),
     *             @OA\Property(property="raid_date_start", type="string", format="date-time", example="2026-06-15T08:00:00Z"),
     *             @OA\Property(property="raid_date_end", type="string", format="date-time", example="2026-06-17T18:00:00Z"),
     *             @OA\Property(property="ins_start_date", type="string", format="date", example="2026-01-01"),
     *             @OA\Property(property="ins_end_date", type="string", format="date", example="2026-05-31"),
     *             @OA\Property(property="raid_description", type="string", example="A thrilling raid through mountains"),
     *             @OA\Property(property="raid_contact", type="string", format="email", example="contact@raid.com"),
     *             @OA\Property(property="raid_street", type="string", example="1 Trail Road"),
     *             @OA\Property(property="raid_city", type="string", example="Chamonix"),
     *             @OA\Property(property="raid_postal_code", type="string", example="74400"),
     *             @OA\Property(property="raid_number", type="integer", example=10),
     *             @OA\Property(property="raid_site_url", type="string", format="uri", example="https://raid.example.com"),
     *             @OA\Property(property="raid_image", type="string", description="Image URL or identifier"),
     *             @OA\Property(property="adh_id", type="integer", description="Responsable Adhérent ID"),
     *             @OA\Property(property="gestionnaire_raid_id", type="integer", example=5, description="User ID to assign as gestionnaire-raid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Raid created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Raid")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - not a responsable-club"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreRaidRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Generate unique raid_number (format: YYYYNNN where NNN is sequential)
        $year = date('Y');
        $lastRaid = Raid::whereYear('created_at', $year)
            ->orderBy('raid_number', 'desc')
            ->first();
        
        if ($lastRaid && $lastRaid->raid_number) {
            // Extract the last sequential number and increment
            $lastNumber = (int) substr($lastRaid->raid_number, -3);
            $validated['raid_number'] = (int) ($year . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT));
        } else {
            // First raid of the year
            $validated['raid_number'] = (int) ($year . '001');
        }
        
        // Set default value for raid_street if not provided (DB field is NOT NULL)
        if (empty($validated['raid_street'])) {
            $validated['raid_street'] = 'Non spécifiée';
        }
        
        // Create registration period first
        $registrationPeriod = \App\Models\RegistrationPeriod::create([
            'ins_start_date' => $validated['ins_start_date'],
            'ins_end_date' => $validated['ins_end_date'],
        ]);
        
        // Add ins_id to raid data
        $validated['ins_id'] = $registrationPeriod->ins_id;
        
        // Remove inscription dates from raid data (they're in registration_period table)
        unset($validated['ins_start_date'], $validated['ins_end_date']);
        
        // Create the raid
        $raid = Raid::create($validated);
        
        return redirect()->route('raids.index')
            ->with('success', 'Raid created successfully.');
    }

    /**
     * Assign gestionnaire-raid role to a user for a specific raid.
     * The user must be an adherent of the club.
     *
     * @param int $userId
     * @param Raid $raid
     * @return void
     */
    protected function assignGestionnaireRaidRole(int $userId, Raid $raid): void
    {
        $targetUser = User::find($userId);
        
        if (!$targetUser) {
            return;
        }

        // Check if the user is an adherent (has valid licence)
        if (!$targetUser->hasRole('adherent')) {
            return;
        }

        // Check if the user is a member of the club
        $member = $targetUser->member;
        if (!$member) {
            return;
        }

        // Assign gestionnaire-raid role
        if (!$targetUser->hasRole('gestionnaire-raid')) {
            $targetUser->assignRole('gestionnaire-raid');
        }

        // Update the raid with the member's adh_id
        $raid->update(['adh_id' => $member->adh_id]);
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/raids/{id}",
     *     tags={"Raids"},
     *     summary="Get raid by ID",
     *     description="Returns a single raid",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Raid")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Raid not found"
     *     )
     * )
     */
    public function show(Raid $raid): Response
    {
        $raid->load(['club', 'races.organizer.user', 'registrationPeriod']);
        
        $user = auth()->user();
        $isRaidManager = $user && ($user->adh_id === $raid->adh_id || $raid->club->hasManager($user));
        
        $courses = $raid->races->map(function($race) use ($user, $isRaidManager) {
            $isRaceManager = $user && ($user->adh_id === $race->adh_id || $isRaidManager);
            
            return [
                'id' => $race->race_id,
                'name' => $race->race_name,
                'organizer_name' => $race->organizer && $race->organizer->user ? $race->organizer->user->name : 'N/A',
                'difficulty' => $race->race_difficulty ?? 'N/A',
                'start_date' => $race->race_date_start ? $race->race_date_start->toIso8601String() : null,
                'image' => $race->image_url,
                'is_open' => $race->isOpen(),
                'registration_upcoming' => $race->isRegistrationUpcoming(),
                'is_finished' => $race->isCompleted(),
                'can_edit' => $isRaceManager,
            ];
        });
        
        // Add status helpers for raid
        $raid->is_open = $raid->isOpen();
        $raid->is_upcoming = $raid->isUpcoming();
        $raid->is_finished = $raid->isFinished();
        
        return Inertia::render('Raid/Index', [
            'raid' => $raid,
            'courses' => $courses,
            'isRaidManager' => $isRaidManager,
            'typeCategories' => \App\Models\ParamType::all()->map(function($t) {
                return [
                    'type_id' => $t->typ_id,
                    'type_name' => $t->typ_name,
                ];
            }),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * Loads raid data with registration period and club members
     */
    public function edit(Raid $raid): Response
    {
        // Check authorization - user must be able to update this raid
        $this->authorize('update', $raid);

        // Load raid with registration period
        $raid->load('registrationPeriod');

        // Get the club of this raid
        $userClub = \DB::table('clubs')
            ->where('club_id', $raid->clu_id)
            ->first(['club_id', 'club_name']);

        // Get members (adherents) of this club from club_user table
        $clubMembers = [];
        if ($userClub) {
            $clubMembers = \DB::table('club_user')
                ->join('users', 'club_user.user_id', '=', 'users.id')
                ->where('club_user.club_id', $userClub->club_id)
                ->whereNotNull('users.adh_id') // Ensure they have an adherent ID
                ->select('users.adh_id', 'users.first_name', 'users.last_name')
                ->get()
                ->map(function ($user) {
                    return [
                        'adh_id' => $user->adh_id,
                        'full_name' => $user->first_name . ' ' . $user->last_name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                    ];
                });
        }

        // Prepare raid data with registration period dates
        $raidData = $raid->toArray();
        if ($raid->registrationPeriod) {
            $raidData['ins_start_date'] = $raid->registrationPeriod->ins_start_date;
            $raidData['ins_end_date'] = $raid->registrationPeriod->ins_end_date;
        }

        return Inertia::render('Raid/Edit', [
            'raid' => $raidData,
            'userClub' => $userClub,
            'clubMembers' => $clubMembers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/raids/{id}",
     *     tags={"Raids"},
     *     summary="Update raid",
     *     description="Updates an existing raid",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Raid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Raid updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Raid")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Raid not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(UpdateRaidRequest $request, Raid $raid): RedirectResponse
    {
        $validated = $request->validated();
        
        // Update registration period if it exists
        if ($raid->ins_id && $raid->registrationPeriod) {
            $raid->registrationPeriod->update([
                'ins_start_date' => $validated['ins_start_date'],
                'ins_end_date' => $validated['ins_end_date'],
            ]);
        }
        
        // Remove inscription dates from raid data (they're in registration_period table)
        unset($validated['ins_start_date'], $validated['ins_end_date']);
        
        // Update the raid
        $raid->update($validated);
        
        return redirect()->route('raids.show', $raid->raid_id)
            ->with('success', 'Raid updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/raids/{id}",
     *     tags={"Raids"},
     *     summary="Delete raid",
     *     description="Deletes a raid",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Raid deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Raid not found"
     *     )
     * )
     */
    public function destroy(Raid $raid): RedirectResponse
    {
        $this->authorize('delete', $raid);

        $raid->delete();
        
        return redirect()->route('raids.index')
            ->with('success', 'Raid deleted successfully.');
    }
}
