<?php

use App\Http\Middleware\AssignDefaultRole;
use App\Http\Middleware\CheckAdminPageAccess;
use App\Http\Middleware\EnsureManagerHasValidLicence;
use App\Http\Middleware\EnsureUserIsClubLeader;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AssignDefaultRole::class,
        ]);

        //
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'club_leader' => EnsureUserIsClubLeader::class,
            'manager_licence' => EnsureManagerHasValidLicence::class,
            'admin_page' => CheckAdminPageAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, Throwable $exception, Request $request) {

            // Return original response (Ignition) if debug mode is on
            if ($response->getStatusCode() === 500 && app()->hasDebugModeEnabled() && app()->isLocal()) {
                return $response;
            }

            if (!in_array($response->getStatusCode(), [401, 403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            $status = $response->getStatusCode();

            // Return JSON for API requests
            // Return JSON for ALL API requests (including invalid methods like GET on POST routes)
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = match($status) {
                    401 => 'Unauthorized',
                    403 => 'Forbidden',
                    404 => 'Not Found',
                    419 => 'Session Expired',
                    422 => 'Unprocessable Entity',
                    429 => 'Too Many Requests',
                    500 => 'Internal Server Error',
                    503 => 'Service Unavailable',
                    default => 'Error ' . $status,
                };

                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                    'data' => [],
                ], $status);
            }

            return Inertia::render('Error', [
                'status' => $status,
                'message' => $response->getContent() // Optional: pass message if needed
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
