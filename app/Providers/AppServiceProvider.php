<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::define('access-admin', function (User $user) {
            return $user->hasRole('admin') || $user->hasAnyPermission(['view users', 'edit users', 'delete users', 'view logs']);
        });

        // Configure Strava OAuth Provider
        $socialite = $this->app->make(SocialiteFactory::class);
        $socialite->extend('strava', function ($app) use ($socialite) {
            $config = $app['config']['services.strava'];
            return $socialite->buildProvider(
                \SocialiteProviders\Strava\Provider::class,
                $config
            );
        });
    }
}
