<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Arr;
use App\Services\LicenceService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        try {
            $parent = parent::share($request);

            $locale = app()->getLocale();

            $translations = [];

            $langPath = base_path('lang/' . $locale);

            if (is_dir($langPath)) {
                foreach (glob($langPath . '/*.php') as $file) {
                    $key = basename($file, '.php');
                    try {
                        $translations[$key] = require $file;
                    } catch (\Throwable $e) {
                        $translations[$key] = [];
                    }
                }
            }

            // Check if user is a manager without valid licence
            $requiresLicenceUpdate = false;
            if ($request->user()) {
                // Load member relationship first
                $request->user()->load('member');
                
                $licenceService = app(LicenceService::class);
                $hasValidLicence = $licenceService->hasValidLicence($request->user());
                $isManager = $request->user()->hasAnyRole([
                    'responsable-club',
                    'gestionnaire-raid',
                    'responsable-course',
                    'gestionnaire-equipe'
                ]);
                
                $requiresLicenceUpdate = $isManager && !$hasValidLicence;
            }

            return [
                ...$parent,
                'auth' => [
                    'user' => $request->user() ? array_merge(
                        $request->user()->load([
                            'roles',
                            'member',
                            'clubs' => function ($query) {
                                $query->where('club_user.status', 'approved')
                                    ->select('clubs.club_id', 'clubs.club_name');
                            }
                        ])->append(['has_completed_profile', 'profile_photo_url'])->toArray(),
                        [
                            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                            'is_club_leader' => $request->user()->isClubLeader(),
                        ]
                    ) : null,
                ],
                'requiresLicenceUpdate' => $requiresLicenceUpdate,
                'locale' => $locale,
                'translations' => $translations,
            ];
        } catch (\Throwable $e) {
            file_put_contents('debug.log', "Exception in Share: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            throw $e;
        }
    }
}
