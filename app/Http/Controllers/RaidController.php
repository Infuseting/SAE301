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
        // TODO: Replace with real data when DB is ready
        // $raids = Raid::latest()->get();
        $raids = [];
        
        return Inertia::render('Raid/List', [
            'raids' => $raids,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Raid/Create');
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
        // TODO: Uncomment when DB is ready
        // $raid = Raid::create($request->validated());
        
        return redirect()->route('raids.index')
            ->with('success', 'Raid will be created when database is ready.');
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
        // TODO: Uncomment when DB is ready
        // $raid->update($request->validated());
        
        return redirect()->route('raids.index')
            ->with('success', 'Raid will be updated when database is ready.');
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
        // TODO: Uncomment when DB is ready
        // $raid->delete();
        
        return redirect()->route('raids.index')
            ->with('success', 'Raid will be deleted when database is ready.');
    }
}
