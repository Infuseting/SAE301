<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * Controller for searching users during team creation.
 * Uses web authentication (session) instead of API authentication (Sanctum).
 */
class UserSearchController extends Controller
{
    use ApiResponseTrait;

    /**
     * Handle user search or list request.
     * If 'q' parameter is provided, search for users.
     * Otherwise, return list of first 50 users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q');

            // If no search query provided, return first 50 users
            if (!$query || strlen(trim($query)) < 2) {
                return $this->listUsers();
            }

            // Otherwise search for users
            return $this->searchUsers($query);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error processing request: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get list of first 50 users with adherent role.
     *
     * @return JsonResponse
     */
    private function listUsers(): JsonResponse
    {
        try {
            $users = User::role('adherent')
                ->select('id', 'first_name', 'last_name', 'email')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(50)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name'  => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Users list retrieved',
                'data' => $users->values()->all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving users: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Search users by name or email.
     * Returns users with the adherent role only.
     *
     * @param string $query
     * @return JsonResponse
     */
    private function searchUsers(string $query): JsonResponse
    {
        try {
            // Search users by first_name, last_name, or email
            // Only include users with 'adherent' role
            $users = User::role('adherent')
                ->where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->select('id', 'first_name', 'last_name', 'email')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name'  => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Users found',
                'data' => $users->values()->all()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->errors(),
                'data' => []
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error searching users: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}








