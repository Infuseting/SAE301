<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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

        // Register Gmail API Transport
        Mail::extend('gmail', function (array $config) {
            // Automatic Token Refresh
            $response = Http::asForm()->withoutVerifying()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $config['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);

            $accessToken = $response->json('access_token');

            if (!$accessToken) {
                throw new \Exception('Gmail API: Failed to refresh access token. Response: ' . $response->body());
            }

            return (new GmailTransportFactory())->create(
                new Dsn(
                    'gmail',
                    'default',
                    $config['username'] ?? config('mail.from.address'),
                    $accessToken,
                    null,
                    [
                        'client_id' => $config['client_id'],
                        'client_secret' => $config['client_secret'],
                    ]
                )
            );
        });
    }
}

