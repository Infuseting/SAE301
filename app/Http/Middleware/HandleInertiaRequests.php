<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Arr;

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

        return [
            ...$parent,
            'auth' => [
                'user' => $request->user(),
            ],
            'locale' => $locale,
            'translations' => $translations,
        ];
    }
}
