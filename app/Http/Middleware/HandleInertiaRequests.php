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

        $authData = null;
        if ($request->user()) {
            $user = $request->user()->load([
                'roles',
                'member',
                'medicalDoc',
                'clubs' => function ($query) {
                    $query->where('club_user.status', 'approved')
                        ->select('clubs.club_id', 'clubs.club_name');
                }
            ])->append(['has_completed_profile', 'profile_photo_url']);

            // Get licence info
            $licenceService = app(LicenceService::class);
            $licenceInfo = $licenceService->getLicenceInfo($request->user());

            $authData = array_merge(
                $user->toArray(),
                [
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                    'roles' => $request->user()->getRoleNames()->toArray(),
                    'licence_info' => $licenceInfo,
                ]
            );
        }

        return [
            ...$parent,
            'auth' => [
                'user' => $request->user() ? array_merge(
                    $request->user()->load([
                        'roles',
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
            'locale' => $locale,
            'translations' => $translations,
        ];
    }
}
