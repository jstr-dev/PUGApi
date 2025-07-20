<?php

namespace App\Providers;

use Event;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\SteamExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, [SteamExtendSocialite::class, 'handle']);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        JsonResource::withoutWrapping();

        if (config('app.debug')) {
            config(['app.url' => getenv('NGROK_URL')]);
            config(['services.slapshot.webhook' => getenv('NGROK_URL') . '/api/slapshot/lobby_webhook']);
            config(['services.steam.redirect' => getenv('NGROK_URL') . '/steam/auth/callback']);

            if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);
            }
        }
    }
}
