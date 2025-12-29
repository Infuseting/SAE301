<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

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
}
