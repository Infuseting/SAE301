<?php

namespace App\Http\Controllers;


use Illuminate\Http\JsonResponse;
use Inertia\Response;
use Inertia\Inertia;
use App\Http\Controllers\Api\ApiResponseTrait;

abstract class Controller
{
    use ApiResponseTrait;

    /**
     * Unified response helper.
     *
     * @param mixed $data The data to return (model, collection, array).
     * @param string $view The Inertia view name.
     * @param array $viewData Additional data for the view (not part of the JSON API response).
     * @return JsonResponse|Response
     */
    protected function respondWith($data, string $view, array $viewData = [])
    {
        if (request()->wantsJson() && !request()->header('X-Inertia')) {
            return $this->successResponse($data);
        }

        return Inertia::render($view, array_merge(['data' => $data], $viewData));
    }
}
