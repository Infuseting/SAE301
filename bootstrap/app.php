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
        ]);

        //
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\Role::class,
            'permission' => \Spatie\Permission\Middleware\Permission::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermission::class,
            'club_leader' => \App\Http\Middleware\EnsureUserIsClubLeader::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response) {
            \Illuminate\Support\Facades\Log::info('Response type: ' . get_class($response));
            if (!in_array($response->getStatusCode(), [401, 403, 404, 419, 429, 500, 503])) {
                return $response;
            }

            return \Inertia\Inertia::render('Error', [
                'status' => $response->getStatusCode(),
                'message' => $response->getContent() // Optional: pass message if needed
            ])
                ->toResponse(request())
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
