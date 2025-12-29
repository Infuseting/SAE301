<?php

namespace App\Http\Controllers;


use OpenApi\Annotations as OA;
use Inertia\Inertia;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="SAER301 API Documentation",
 *      description="Auto-generated API documentation for SAE301 project",
 *      @OA\Contact(
 *          email="admin@example.com"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Production API Server"
 * 
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Login with email and password to get the authentication token",
 *     name="Token based Based",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="apiAuth",
 * )
 */
abstract class Controller
{
    /**
     * Unified response helper.
     *
     * @param mixed $data The data to return (model, collection, array).
     * @param string $view The Inertia view name.
     * @param array $viewData Additional data for the view (not part of the JSON API response).
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response
     */
    protected function respondWith($data, string $view, array $viewData = [])
    {
        if (request()->wantsJson() && !request()->header('X-Inertia')) {
            return response()->json($data);
        }

        return Inertia::render($view, array_merge(['data' => $data], $viewData));
    }
}
