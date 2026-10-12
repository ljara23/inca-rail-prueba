<?php

namespace App\Providers;

use App\Services\FootballData\FootballDataClient;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FootballDataClient::class, function () {
            return new FootballDataClient(
                config('services.football_data.base_url'),
                config('services.football_data.token')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::loadKeysFrom(storage_path('oauth'));
    }
}
