<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * Trait for uniform API responses across all API endpoints.
 * Ensures a consistent JSON response format: { status, message, data }
 */
trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data The data payload (array, collection, model, etc.)
     * @param string $message A human-readable success message.
     * @param int $statusCode HTTP status code (default 200).
     * @return JsonResponse
     */
    protected function  successResponse(mixed $data = null, string $message = 'OK', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data ?? [],
        ], $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message A human-readable error message.
     * @param int $statusCode HTTP status code (default 400).
     * @param mixed $errors Optional validation errors or additional error details.
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'data' => [],
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an unauthorized JSON response.
     *
     * @param string $message A human-readable unauthorized message.
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a forbidden JSON response.
     *
     * @param string $message A human-readable forbidden message.
     * @param mixed $errors Optional validation errors or additional error details.
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden', mixed $errors = null): JsonResponse
    {
        return $this->errorResponse($message, 403, $errors);
    }

    /**
     * Return a not found JSON response.
     *
     * @param string $message A human-readable not-found message.
     * @param mixed|null $errors
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found', mixed $errors = null): JsonResponse
    {
        return $this->errorResponse($message, 404, $errors);
    }

    /**
     * Return a server error JSON response
     *
     * @param mixed $errors Optional validation errors or additional error details.
     */
    protected function unprocessableContentResponse(string $message = 'Unprocessable Content', mixed $errors = null): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return a server error JSON response
     *
     * @param mixed $errors Optional validation errors or additional error details.
     */
    protected function serverErrorResponse(string $message = 'Server Error', mixed $errors = null): JsonResponse
    {
        return $this->errorResponse($message, 500, $errors);
    }

    /**
     * Return a paginated success JSON response.
     * Merges status and message at top level alongside paginated data.
     *
     * @param array $paginatedData Array containing 'data', 'total', 'current_page', etc.
     * @param string $message A human-readable success message.
     * @return JsonResponse
     */
    protected function paginatedResponse(array $paginatedData, string $message = 'OK'): JsonResponse
    {
        return response()->json(array_merge([
            'status' => 'success',
            'message' => $message,
        ], $paginatedData));
    }
}
