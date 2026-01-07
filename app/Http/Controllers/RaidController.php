<?php

namespace App\Http\Controllers;

use App\Models\Raid;
use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRaidRequest;
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
     * 
     * @OA\Get(
     *     path="/raids",
     *     tags={"Raids"},
     *     summary="Get list of raids",
     *     description="Returns list of all raids",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Raid")
     *         )
     *     )
     * )
     */
    public function index(): Response
    {
        $raids = Raid::latest()->get();
        
        return Inertia::render('Raid/List', [
            'raids' => $raids,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * Only responsable-club can create raids for their club.
     */
    public function create(): Response
    {
        $this->authorize('create', Raid::class);

        // Get clubs the user is responsible for
        $user = auth()->user();
        $clubs = $user->clubs()->where('is_approved', true)->get();

        return Inertia::render('Raid/Create', [
            'clubs' => $clubs,
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
     *             required={"raid_name", "clu_id", "raid_date_start", "raid_date_end"},
     *             @OA\Property(property="raid_name", type="string", example="Mountain Adventure Raid"),
     *             @OA\Property(property="clu_id", type="integer", example=1, description="Club ID"),
     *             @OA\Property(property="raid_date_start", type="string", format="date-time", example="2026-06-15T08:00:00Z"),
     *             @OA\Property(property="raid_date_end", type="string", format="date-time", example="2026-06-17T18:00:00Z"),
     *             @OA\Property(property="raid_description", type="string", example="A thrilling raid through mountains"),
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
        $this->authorize('create', Raid::class);

        $user = auth()->user();
        $validated = $request->validated();

        // Verify user can create raid for this club
        $club = Club::findOrFail($validated['clu_id']);
        $this->authorize('createForClub', [Raid::class, $club]);

        // Create the raid
        $raid = Raid::create($validated);

        // If a gestionnaire_raid_id is specified, assign the role
        if (!empty($validated['gestionnaire_raid_id'])) {
            $this->assignGestionnaireRaidRole($validated['gestionnaire_raid_id'], $raid);
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
        // TODO: Load courses when DB is ready
        $courses = [];
        
        return Inertia::render('Raid/Index', [
            'raid' => $raid,
            'courses' => $courses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Raid $raid): Response
    {
        $this->authorize('update', $raid);

        $user = auth()->user();
        $clubs = $user->hasRole('admin') 
            ? Club::where('is_approved', true)->get()
            : $user->clubs()->where('is_approved', true)->get();

        // Get adherents of the club for gestionnaire-raid assignment
        $clubAdherents = [];
        if ($raid->clu_id) {
            $clubAdherents = User::whereHas('member')
                ->whereHas('roles', function($q) {
                    $q->where('name', 'adherent');
                })
                ->whereHas('clubs', function($q) use ($raid) {
                    $q->where('clubs.club_id', $raid->clu_id);
                })
                ->get(['id', 'name', 'email']);
        }

        return Inertia::render('Raid/Edit', [
            'raid' => $raid,
            'clubs' => $clubs,
            'clubAdherents' => $clubAdherents,
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
    public function update(StoreRaidRequest $request, Raid $raid): RedirectResponse
    {
        $this->authorize('update', $raid);

        $validated = $request->validated();
        $raid->update($validated);

        // If a gestionnaire_raid_id is specified, assign the role
        if (!empty($validated['gestionnaire_raid_id'])) {
            $this->assignGestionnaireRaidRole($validated['gestionnaire_raid_id'], $raid);
        }
        
        return redirect()->route('raids.index')
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
