<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $supportedLocales = ['en', 'es', 'fr', 'de'];

            // 1. Check if user has explicit preference in session
            $locale = $request->session()->get('locale');

            // 2. If not, check browser's preferred language
            if (!$locale) {
                $locale = $request->getPreferredLanguage($supportedLocales);
            }

            if ($locale && in_array($locale, $supportedLocales)) {
                app()->setLocale($locale);
            }
        } catch (\Throwable $e) {
            // session may not be available in some contexts
        }

        return $next($request);
    }
}
