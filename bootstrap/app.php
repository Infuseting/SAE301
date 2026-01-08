<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\AssignDefaultRole::class,
        ]);

        //
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\Role::class,
            'permission' => \Spatie\Permission\Middleware\Permission::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermission::class,
            'club_leader' => \App\Http\Middleware\EnsureUserIsClubLeader::class,
            'manager_licence' => \App\Http\Middleware\EnsureManagerHasValidLicence::class,
            'admin_page' => \App\Http\Middleware\CheckAdminPageAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, \Throwable $exception, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::info('Response type: ' . get_class($response));

            // Return original response (Ignition) if debug mode is on
            if (app()->hasDebugModeEnabled() && app()->isLocal() && $response->getStatusCode() === 500) {
                return $response;
            }

            if (!in_array($response->getStatusCode(), [401, 403, 404, 419, 429, 500, 503])) {
                return $response;
            }

            $status = $response->getStatusCode();

            return \Inertia\Inertia::render('Error', [
                'status' => $status,
                'message' => $response->getContent() // Optional: pass message if needed
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
