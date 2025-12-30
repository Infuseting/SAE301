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
            $locale = $request->session()->get('locale', null);
            if ($locale && in_array($locale, ['en', 'es', 'fr'])) {
                app()->setLocale($locale);
            }
        } catch (\Throwable $e) {
            // session may not be available in some contexts
        }

        return $next($request);
    }
}
