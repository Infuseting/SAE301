<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Get the authenticated user.
     *
     * @OA\Get(
     *      path="/api/user",
     *      operationId="getApiUser",
     *      tags={"User"},
     *      summary="Get authenticated user",
     *      description="Returns the currently authenticated user",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      security={{"apiAuth": {}}}
     * )
     */
    public function __invoke(Request $request)
    {
        return $request->user();
    }

    /**
     * Search users for team creation.
     *
     * @OA\Get(
     *      path="/api/users/search",
     *      operationId="searchUsers",
     *      tags={"User"},
     *      summary="Search users",
     *      description="Returns users matching the search query for team creation",
     *      @OA\Parameter(
     *          name="q",
     *          in="query",
     *          description="Search query (name or email)",
     *          required=true,
     *          @OA\Schema(type="string", minLength=2)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="first_name", type="string"),
     *                  @OA\Property(property="last_name", type="string"),
     *                  @OA\Property(property="email", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Query parameter is required"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      security={{"apiAuth": {}}}
     * )
     */
    public function search(Request $request)
    {
        // Validate query parameter
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:255',
        ], [
            'q.required' => 'Search query is required',
            'q.min' => 'Search query must be at least 2 characters',
        ]);

        $query = $validated['q'];

        // Search users by first_name, last_name, or email
        $users = User::where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->select('id', 'first_name', 'last_name', 'email')
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name'  => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
            ];
        });

        return response()->json($users);
    }
}
