<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Leaderboard",
 *     description="API endpoints for leaderboard management"
 * )
 */
class LeaderboardApiController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/leaderboard/races",
     *     tags={"Leaderboard"},
     *     summary="Get list of races with leaderboards",
     *     @OA\Response(
     *         response=200,
     *         description="List of races",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="race_id", type="integer"),
     *                     @OA\Property(property="race_name", type="string"),
     *                     @OA\Property(property="race_date_start", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function races(): JsonResponse
    {
        $races = $this->leaderboardService->getRaces();

        return response()->json([
            'success' => true,
            'data' => $races,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/leaderboard/{raceId}/individual",
     *     tags={"Leaderboard"},
     *     summary="Get individual leaderboard for a race",
     *     @OA\Parameter(
     *         name="raceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search by user name or email"
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Individual leaderboard results",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/LeaderboardUser")
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function individual(Request $request, int $raceId): JsonResponse
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 20);

        $results = $this->leaderboardService->getIndividualLeaderboard($raceId, $search, $perPage);

        return response()->json([
            'success' => true,
            ...$results,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/leaderboard/{raceId}/teams",
     *     tags={"Leaderboard"},
     *     summary="Get team leaderboard for a race",
     *     @OA\Parameter(
     *         name="raceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search by team name"
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Team leaderboard results",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/LeaderboardTeam")
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function teams(Request $request, int $raceId): JsonResponse
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 20);

        $results = $this->leaderboardService->getTeamLeaderboard($raceId, $search, $perPage);

        return response()->json([
            'success' => true,
            ...$results,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/leaderboard/{raceId}/user/{userId}",
     *     tags={"Leaderboard"},
     *     summary="Get specific user result in a race",
     *     @OA\Parameter(
     *         name="raceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User result",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/LeaderboardUser")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Result not found"
     *     )
     * )
     */
    public function userResult(int $raceId, int $userId): JsonResponse
    {
        $result = \App\Models\LeaderboardUser::with('user:id,first_name,last_name')
            ->where('race_id', $raceId)
            ->where('user_id', $userId)
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found',
            ], 404);
        }

        $rank = \App\Models\LeaderboardUser::where('race_id', $raceId)
            ->where('temps_final', '<', $result->temps_final)
            ->count() + 1;

        return response()->json([
            'success' => true,
            'data' => [
                'rank' => $rank,
                'id' => $result->id,
                'user_id' => $result->user_id,
                'user_name' => $result->user ? $result->user->first_name . ' ' . $result->user->last_name : 'Unknown',
                'temps' => $result->temps,
                'temps_formatted' => $result->formatted_temps,
                'malus' => $result->malus,
                'malus_formatted' => $result->formatted_malus,
                'temps_final' => $result->temps_final,
                'temps_final_formatted' => $result->formatted_temps_final,
            ],
        ]);
    }
}
