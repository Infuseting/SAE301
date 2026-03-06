<?php

namespace App\Http\Controllers;


use OpenApi\Annotations as OA;
use Inertia\Inertia;

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
