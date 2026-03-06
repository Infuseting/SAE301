<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use App\Models\Race;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * API controller for public race endpoints.
 * Returns JSON responses with uniform format.
 */
class RaceApiController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get a list of all races.
     *
     * @OA\Get(
     *     path="/api/races",
     *     tags={"Races"},
     *     summary="Get list of races",
     *     description="Returns all races with related data",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by race name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Races retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Race")
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Race::with(['raid.club', 'type', 'organizer.user'])
            ->orderBy('race_date_start', 'desc');

        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where('race_name', 'like', "%{$search}%");
        }

        $races = $query->get()->map(function ($race) {
            return [
                'race_id' => $race->race_id,
                'race_name' => $race->race_name,
                'race_description' => $race->race_description,
                'race_date_start' => $race->race_date_start?->toIso8601String(),
                'race_date_end' => $race->race_date_end?->toIso8601String(),
                'race_difficulty' => $race->race_difficulty,
                'race_duration_minutes' => $race->race_duration_minutes,
                'type' => $race->type?->typ_name ?? 'Classique',
                'image_url' => $race->image_url ? '/storage/' . $race->image_url : null,
                'price_major' => $race->price_major,
                'price_minor' => $race->price_minor,
                'is_open' => $race->isOpen(),
                'raid' => $race->raid ? [
                    'id' => $race->raid->raid_id,
                    'name' => $race->raid->raid_name,
                    'city' => $race->raid->raid_city,
                    'club' => $race->raid->club ? [
                        'id' => $race->raid->club->club_id,
                        'name' => $race->raid->club->club_name,
                    ] : null,
                ] : null,
                'organizer' => $race->organizer?->user ? [
                    'name' => trim($race->organizer->user->first_name . ' ' . $race->organizer->user->last_name),
                ] : null,
            ];
        });

        return $this->successResponse($races, 'Races retrieved successfully');
    }

    /**
     * Get a specific race by ID.
     *
     * @OA\Get(
     *     path="/api/races/{id}",
     *     tags={"Races"},
     *     summary="Get race by ID",
     *     description="Returns a single race with details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Race retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Race")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Race not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Race not found"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $race = Race::with([
            'raid.club',
            'type',
            'organizer.user',
            'categorieAges.ageCategory',
            'teamParams',
            'runnerParams',
        ])->find($id);

        if (!$race) {
            return $this->notFoundResponse('Race not found');
        }

        $raceData = [
            'race_id' => $race->race_id,
            'race_name' => $race->race_name,
            'race_description' => $race->race_description,
            'race_date_start' => $race->race_date_start?->toIso8601String(),
            'race_date_end' => $race->race_date_end?->toIso8601String(),
            'race_difficulty' => $race->race_difficulty,
            'race_duration_minutes' => $race->race_duration_minutes,
            'type' => $race->type?->typ_name ?? 'Classique',
            'image_url' => $race->image_url ? '/storage/' . $race->image_url : null,
            'price_major' => $race->price_major,
            'price_minor' => $race->price_minor,
            'is_open' => $race->isOpen(),
            'raid' => $race->raid ? [
                'id' => $race->raid->raid_id,
                'name' => $race->raid->raid_name,
                'city' => $race->raid->raid_city,
                'club' => $race->raid->club ? [
                    'id' => $race->raid->club->club_id,
                    'name' => $race->raid->club->club_name,
                ] : null,
            ] : null,
            'organizer' => $race->organizer?->user ? [
                'name' => trim($race->organizer->user->first_name . ' ' . $race->organizer->user->last_name),
            ] : null,
            'age_categories' => $race->categorieAges->map(function ($categorieAge) {
                return [
                    'id' => $categorieAge->ageCategory->id ?? null,
                    'nom' => $categorieAge->ageCategory->nom ?? null,
                    'age_min' => $categorieAge->ageCategory->age_min ?? null,
                    'age_max' => $categorieAge->ageCategory->age_max ?? null,
                ];
            })->values(),
        ];

        return $this->successResponse($raceData, 'Race retrieved successfully');
    }
}
