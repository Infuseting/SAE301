<?php

namespace App\Http\Controllers;

use App\Models\Raid;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRaidRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use OpenApi\Annotations as OA;

class RaidController extends Controller
{
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
     * 
     * @OA\Post(
     *     path="/raids",
     *     tags={"Raids"},
     *     summary="Create a new raid",
     *     description="Creates a new raid event",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "event_start_date", "event_end_date", "registration_start_date", "registration_end_date"},
     *             @OA\Property(property="name", type="string", example="Mountain Adventure Raid"),
     *             @OA\Property(property="event_start_date", type="string", format="date-time", example="2026-06-15T08:00:00Z"),
     *             @OA\Property(property="event_end_date", type="string", format="date-time", example="2026-06-17T18:00:00Z"),
     *             @OA\Property(property="registration_start_date", type="string", format="date-time", example="2026-03-01T00:00:00Z"),
     *             @OA\Property(property="registration_end_date", type="string", format="date-time", example="2026-06-10T23:59:59Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Raid created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Raid")
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
        return Inertia::render('Raid/Edit', [
            'raid' => $raid,
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
        $raid->update($request->validated());
        
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
     *         response=404,
     *         description="Raid not found"
     *     )
     * )
     */
    public function destroy(Raid $raid): RedirectResponse
    {
        $raid->delete();
        
        return redirect()->route('raids.index')
            ->with('success', 'Raid deleted successfully.');
    }
}
