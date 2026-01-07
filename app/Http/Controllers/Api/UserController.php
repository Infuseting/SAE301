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
     *      path="/users/search",
     *      operationId="searchUsers",
     *      tags={"User"},
     *      summary="Search users",
     *      description="Returns users matching the search query",
     *      @OA\Parameter(
     *          name="q",
     *          in="query",
     *          description="Search query",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="email", type="string")
     *              )
     *          )
     *      ),
     *      security={{"apiAuth": {}}}
     * )
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        $users = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->select('id', 'name', 'email')
        ->limit(20)
        ->get();

        return response()->json($users);
    }
}
