<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for user's personal leaderboard results.
 * Allows authenticated users to view their own race results.
 */
class MyLeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {}

    /**
     * Display the user's personal leaderboard results.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $search = $request->input('search');
        $sortBy = $request->input('sort', 'best');

        $results = $this->leaderboardService->getUserResults(
            $user->id,
            $search,
            $sortBy
        );

        return Inertia::render('MyLeaderboard/Index', [
            'results' => $results,
            'search' => $search,
            'sortBy' => $sortBy,
        ]);
    }
}
