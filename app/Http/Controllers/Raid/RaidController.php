<?php

namespace App\Http\Controllers\Raid;

use App\Models\Raid;
use App\Models\AgeCategory;
use App\Data\FranceDepartments;
use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRaidRequest;
use App\Http\Requests\UpdateRaidRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        // Build query for raids
        $query = Raid::query()
            ->with(['club:club_id,club_name', 'registrationPeriod:ins_id,ins_start_date,ins_end_date'])
            ->withCount('races');

        // Filter by date if provided
        if ($request->has('date') && !empty($request->input('date'))) {
            $filterDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay();
            $query->where(function ($q) use ($filterDate) {
                $q->whereBetween('raid_date_start', [
                    $filterDate,
                    $filterDate->copy()->endOfDay()
                ])
                ->orWhereBetween('raid_date_end', [
                    $filterDate,
                    $filterDate->copy()->endOfDay()
                ])
                ->orWhere(function ($subQ) use ($filterDate) {
                    $subQ->where('raid_date_start', '<=', $filterDate)
                      ->where('raid_date_end', '>=', $filterDate);
                });
            });
        }

        // Filter by category (race type) if provided
        if ($request->has('category') && !empty($request->input('category')) && $request->input('category') !== 'all') {
            $categoryFilter = $request->input('category');
            // Map 'competition' to 'compétitif' for database matching
            $typeName = $categoryFilter === 'competition' ? 'compétitif' : $categoryFilter;
            $query->whereHas('races', function ($q) use ($typeName) {
                $q->whereHas('type', function ($subQ) use ($typeName) {
                    $subQ->where('param_type.typ_name', $typeName);
                });
            });
        }

        // Filter by age category if provided
        if ($request->has('age_category') && !empty($request->input('age_category'))) {
            $ageCategoryName = $request->input('age_category');
            $query->whereHas('races', function ($q) use ($ageCategoryName) {
                $q->whereHas('categorieAges.ageCategory', function ($subQ) use ($ageCategoryName) {
                    $subQ->where('age_categories.nom', $ageCategoryName);
                });
            });
        }

        // Filter by location (city, department, or region) if provided
        if ($request->has('location') && !empty($request->input('location')) && $request->has('location_type')) {
            $location = $request->input('location');
            $locationType = $request->input('location_type');

            if ($locationType === 'city') {
                // Search by city name
                $query->where('raid_city', 'like', '%' . $location . '%');
            } elseif ($locationType === 'department') {
                // Search by department name - get all postal code prefixes for this department
                $departments = FranceDepartments::getDepartments();
                $postalCodes = array_keys(array_filter($departments, fn($dept) => 
                    strtolower($dept['name']) === strtolower($location)
                ));
                
                if (!empty($postalCodes)) {
                    $query->where(function ($q) use ($postalCodes) {
                        foreach ($postalCodes as $code) {
                            $q->orWhere('raid_postal_code', 'like', $code . '%');
                        }
                    });
                }
            } elseif ($locationType === 'region') {
                // Search by region - get all postal codes for this region
                $departments = FranceDepartments::getDepartments();
                $postalCodes = array_keys(array_filter($departments, fn($dept) => 
                    strtolower($dept['region']) === strtolower($location)
                ));
                
                if (!empty($postalCodes)) {
                    $query->where(function ($q) use ($postalCodes) {
                        foreach ($postalCodes as $code) {
                            $q->orWhere('raid_postal_code', 'like', $code . '%');
                        }
                    });
                }
            }
        }

        $raids = $query->orderBy('raid_date_start', 'asc')->get();

        // Get all age categories for the filter
        $ageCategories = AgeCategory::all();

        return Inertia::render('Raid/List', [
            'raids' => $raids,
            'ageCategories' => $ageCategories,
            'filters' => [
                'q' => $request->input('q', ''),
                'date' => $request->input('date', ''),
                'category' => $request->input('category', 'all'),
                'age_category' => $request->input('age_category', ''),
                'location' => $request->input('location', ''),
                'location_type' => $request->input('location_type', 'city'),
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
        $clubMembers = collect();
        if ($userClub) {
            // Get all approved members of the club who have an adh_id (are adherents)
            $clubMembers = \DB::table('club_user')
                ->join('users', 'club_user.user_id', '=', 'users.id')
                ->where('club_user.club_id', $userClub->club_id)
                ->where('club_user.status', 'approved')
                ->whereNotNull('users.adh_id')
                ->select('users.id', 'users.adh_id', 'users.first_name', 'users.last_name', 'users.email')
                ->orderBy('users.last_name')
                ->orderBy('users.first_name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'adh_id' => $user->adh_id,
                        'name' => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email,
                    ];
                });

            // If current user is not in the list but is the club creator and has adh_id, add them
            $currentUserId = auth()->id();
            $currentUser = auth()->user();
            $currentUserInList = $clubMembers->contains('id', $currentUserId);
            
            if (!$currentUserInList && $currentUser->adh_id) {
                $clubMembers->prepend([
                    'id' => $currentUser->id,
                    'adh_id' => $currentUser->adh_id,
                    'name' => trim($currentUser->first_name . ' ' . $currentUser->last_name),
                    'email' => $currentUser->email,
                ]);
            }

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
        
        // Handle image upload
        if ($request->hasFile('raid_image')) {
            $imagePath = $request->file('raid_image')->store('raids', 'public');
            $validated['raid_image'] = $imagePath;
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

        // Handle image upload if present
        if ($request->hasFile('raid_image')) {
            $imagePath = $request->file('raid_image')->store('raids', 'public');
            $validated['raid_image'] = $imagePath;
        }

        // Create the raid
        $raid = Raid::create($validated);

        // Assign gestionnaire-raid role to the designated responsible if adh_id is set
        if (!empty($validated['adh_id'])) {
            $responsibleUser = User::where('adh_id', $validated['adh_id'])->first();
            if ($responsibleUser) {
                $this->assignGestionnaireRaidRole($responsibleUser->id, $raid);
            }
        }

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

        // Check if the user is an adherent (has valid licence or adh_id)
        if (!$targetUser->adh_id) {
            return;
        }

        // Check if the user is a member of the raid's club
        $isMemberOfClub = $targetUser->clubs()
            ->where('clubs.club_id', $raid->clu_id)
            ->wherePivot('status', 'approved')
            ->exists();

        if (!$isMemberOfClub) {
            return;
        }

        // Assign gestionnaire-raid role (even if user has other roles like admin or responsable-club)
        if (!$targetUser->hasRole('gestionnaire-raid')) {
            $targetUser->assignRole('gestionnaire-raid');
            
            activity()
                ->performedOn($raid)
                ->causedBy(auth()->user())
                ->withProperties(['user' => $targetUser->first_name . ' ' . $targetUser->last_name])
                ->log('User assigned as raid manager');
        }
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
        $raid->load(['club', 'races.organizer.user', 'races.categorieAges.ageCategory', 'registrationPeriod']);

        $user = auth()->user();
        // Admin can manage all raids, otherwise check if user is raid manager or club manager
        $isRaidManager = $user && ($user->hasRole('admin') || $user->adh_id === $raid->adh_id || $raid->club->hasManager($user));

        $courses = $raid->races->map(function ($race) use ($user, $isRaidManager) {
            $isRaceManager = $user && ($user->adh_id === $race->adh_id || $isRaidManager);

            // Check if user is already registered to this race
            $isRegistered = false;
            if ($user) {
                $isRegistered = \DB::table('registration')
                    ->join('has_participate', 'registration.equ_id', '=', 'has_participate.equ_id')
                    ->where('registration.race_id', $race->race_id)
                    ->where('has_participate.id_users', $user->id)
                    ->exists();
            }

            // Map age categories for display
            $ageCategories = $race->categorieAges->map(function ($categorieAge) {
                return [
                    'id' => $categorieAge->ageCategory->id,
                    'nom' => $categorieAge->ageCategory->nom,
                    'age_min' => $categorieAge->ageCategory->age_min,
                    'age_max' => $categorieAge->ageCategory->age_max,
                ];
            })->values();

            return [
                'id' => $race->race_id,
                'name' => $race->race_name,
                'organizer_name' => $race->organizer && $race->organizer->user ? $race->organizer->user->name : 'N/A',
                'difficulty' => $race->race_difficulty ?? 'N/A',
                'start_date' => $race->race_date_start ? $race->race_date_start->toIso8601String() : null,
                'duration_minutes' => $race->race_duration_minutes ?? 0,
                'ageCategories' => $ageCategories,
                'image' => $race->image_url ? '/storage/' . $race->image_url : null,
                'is_open' => $race->isOpen(),
                'registration_upcoming' => $race->isRegistrationUpcoming(),
                'is_finished' => $race->isCompleted(),
                'can_edit' => $isRaceManager,
                'is_registered' => $isRegistered,
            ];
        });

        // Add status helpers for raid
        $raid->is_open = $raid->isOpen();
        $raid->is_upcoming = $raid->isUpcoming();
        $raid->is_finished = $raid->isFinished();

        // Get registered members for raid managers
        $registeredMembers = collect();
        if ($isRaidManager) {
            // Get all race IDs for this raid
            $raceIds = $raid->races->pluck('race_id');
            
            // Get unique users registered through the registration table and has_participate
            $registeredMembers = \DB::table('registration')
                ->join('has_participate', 'registration.equ_id', '=', 'has_participate.equ_id')
                ->join('users', 'has_participate.id_users', '=', 'users.id')
                ->whereIn('registration.race_id', $raceIds)
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->distinct()
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                    ];
                });
        }

        return Inertia::render('Raid/Index', [
            'raid' => $raid,
            'courses' => $courses,
            'isRaidManager' => $isRaidManager,
            'canEditRaid' => $user && $user->can('update', $raid),
            'canAddRace' => $user && $user->can('create', [\App\Models\Race::class, $raid]),
            'registeredMembers' => $registeredMembers,
            'typeCategories' => \App\Models\ParamType::all()->map(function ($t) {
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

        // Load registration period for the form
        $raid->load('registrationPeriod');

        // Get the club of this raid
        $userClub = \DB::table('clubs')
            ->where('club_id', $raid->clu_id)
            ->first(['club_id', 'club_name']);

        // Get adherents of the club for gestionnaire-raid assignment
        $clubMembers = collect();
        if ($raid->clu_id) {
            // Get all approved members of the club
            $clubMembers = \DB::table('club_user')
                ->join('users', 'club_user.user_id', '=', 'users.id')
                ->where('club_user.club_id', $raid->clu_id)
                ->where('club_user.status', 'approved')
                ->select('users.id', 'users.adh_id', 'users.first_name', 'users.last_name', 'users.email')
                ->orderBy('users.last_name')
                ->orderBy('users.first_name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'adh_id' => $user->adh_id,
                        'name' => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email,
                    ];
                });

            // If current user is not in the list but has access to edit, add them
            $currentUserId = auth()->id();
            $currentUserInList = $clubMembers->contains('id', $currentUserId);
            
            if (!$currentUserInList) {
                $currentUser = auth()->user();
                $clubMembers->prepend([
                    'id' => $currentUser->id,
                    'adh_id' => $currentUser->adh_id,
                    'name' => trim($currentUser->first_name . ' ' . $currentUser->last_name),
                    'email' => $currentUser->email,
                ]);
            }
        }

        return Inertia::render('Raid/Edit', [
            'raid' => $raid,
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
        // Check authorization
        $this->authorize('update', $raid);
        
        $validated = $request->validated();
        
        // Handle image upload if provided
        if ($request->hasFile('raid_image')) {
            // Delete old image if exists
            if ($raid->raid_image) {
                Storage::disk('public')->delete($raid->raid_image);
            }
            
            // Store new image
            $path = $request->file('raid_image')->store('raids', 'public');
            $validated['raid_image'] = $path;
        } elseif (!$request->has('raid_image')) {
            // If no image in request, keep existing
            unset($validated['raid_image']);
        }
        
        // Update registration period if it exists
        if ($raid->ins_id && $raid->registrationPeriod) {
            $raid->registrationPeriod->update([
                'ins_start_date' => $validated['ins_start_date'],
                'ins_end_date' => $validated['ins_end_date'],
            ]);
        }

        // Remove inscription dates from raid data (they're in registration_period table)
        unset($validated['ins_start_date'], $validated['ins_end_date']);

        // Handle image upload if present
        if ($request->hasFile('raid_image')) {
            $imagePath = $request->file('raid_image')->store('raids', 'public');
            $validated['raid_image'] = $imagePath;
        } else {
            // Don't overwrite existing image if no new image uploaded
            unset($validated['raid_image']);
        }

        // Update the raid
        $raid->update($validated);

        // Assign gestionnaire-raid role to the designated responsible if adh_id is set or updated
        if (!empty($validated['adh_id'])) {
            $responsibleUser = User::where('adh_id', $validated['adh_id'])->first();
            if ($responsibleUser) {
                $this->assignGestionnaireRaidRole($responsibleUser->id, $raid);
            }
        }

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

    /**
     * Display the QR code scanner page for check-in
     *
     * @OA\Get(
     *     path="/raids/{raid}/scanner",
     *     summary="Display QR code scanner page",
     *     tags={"Raids"},
     *     @OA\Parameter(
     *         name="raid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Scanner page displayed"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function scannerPage(Raid $raid): Response
    {
        $user = auth()->user();
        
        // Check if user can manage this raid
        $isRaidManager = $user && (
            $user->hasRole('admin') || 
            ($raid->club && $raid->club->created_by === $user->id)
        );

        if (!$isRaidManager) {
            abort(403, 'Unauthorized. Only raid managers can access the scanner.');
        }

        // Get all registrations for this raid with team info
        $registrations = \App\Models\Registration::with(['team.leader', 'race'])
            ->whereHas('race', function ($query) use ($raid) {
                $query->where('raid_id', $raid->raid_id);
            })
            ->where('reg_validated', true)
            ->get()
            ->map(function ($registration) {
                return [
                    'reg_id' => $registration->reg_id,
                    'equ_id' => $registration->equ_id,
                    'team_name' => $registration->team->equ_name ?? 'Unknown',
                    'leader_name' => $registration->team->leader 
                        ? $registration->team->leader->first_name . ' ' . $registration->team->leader->last_name 
                        : 'Unknown',
                    'race_name' => $registration->race->race_name ?? 'Unknown',
                    'dossard' => $registration->reg_dossard,
                    'is_present' => $registration->is_present,
                    'qr_code_url' => $registration->qr_code_url,
                ];
            });

        return Inertia::render('Raid/Scanner', [
            'raid' => [
                'raid_id' => $raid->raid_id,
                'raid_name' => $raid->raid_name,
            ],
            'registrations' => $registrations,
        ]);
    }

    /**
     * Check-in a team via QR code scan
     *
     * @OA\Post(
     *     path="/raids/{raid}/check-in",
     *     summary="Check-in a team",
     *     tags={"Raids"},
     *     @OA\Parameter(
     *         name="raid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="equ_id", type="integer"),
     *             @OA\Property(property="reg_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Team checked in"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Registration not found")
     * )
     */
    public function checkIn(Request $request, Raid $raid)
    {
        // Check if user is raid manager (club creator)
        $user = auth()->user();
        $isRaidManager = $user && $raid->club && $raid->club->created_by === $user->id;

        if (!$isRaidManager) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only raid managers can check-in teams.'
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

        // Check if registration belongs to this raid
        if ($registration->race->raid_id !== $raid->raid_id) {
            return response()->json([
                'success' => false,
                'message' => 'This registration does not belong to this raid.'
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
                    'leader_name' => $registration->team->leader 
                        ? $registration->team->leader->first_name . ' ' . $registration->team->leader->last_name 
                        : 'Unknown',
                    'is_present' => true,
                ],
            ]);
        }

        // Mark as present
        $registration->update(['is_present' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Team successfully checked in!',
            'registration' => [
                'reg_id' => $registration->reg_id,
                'reg_dossard' => $registration->reg_dossard,
                'team_name' => $registration->team->equ_name,
                'race_name' => $registration->race->race_name,
                'leader_name' => $registration->team->leader 
                    ? $registration->team->leader->first_name . ' ' . $registration->team->leader->last_name 
                    : 'Unknown',
                'is_present' => true,
            ],
        ]);
    }

    /**
     * Generate start-list PDF for the raid
     *
     * @OA\Get(
     *     path="/raids/{raid}/start-list",
     *     summary="Generate start-list PDF",
     *     tags={"Raids"},
     *     @OA\Parameter(
     *         name="raid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="PDF generated"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function generateStartList(Raid $raid)
    {
        $user = auth()->user();
        
        // Check if user can manage this raid
        $isRaidManager = $user && (
            $user->hasRole('admin') || 
            ($raid->club && $raid->club->created_by === $user->id)
        );

        if (!$isRaidManager) {
            abort(403, 'Unauthorized. Only raid managers can generate start lists.');
        }

        // Get all races for this raid
        $races = $raid->races()->get();

        // Get all registrations for this raid grouped by race (all, not just validated)
        $racesByCategory = $races->map(function ($race) {
            // Attach all registrations to the race object for check-in on event day
            $race->teams = \App\Models\Registration::with(['team.leader', 'team.users'])
                ->where('race_id', $race->race_id)
                ->orderBy('reg_dossard')
                ->orderBy('created_at')
                ->get();

            return $race;
        });

        // Count total teams
        $totalTeams = $racesByCategory->sum(function ($race) {
            return $race->teams->count();
        });

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.start-list', [
            'raid' => $raid,
            'racesByCategory' => $racesByCategory,
            'totalTeams' => $totalTeams,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $filename = 'start-list-' . \Illuminate\Support\Str::slug($raid->raid_name) . '.pdf';

        return $pdf->download($filename);
    }
}
