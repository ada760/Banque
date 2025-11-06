<?php

namespace App\Providers;

use App\Models\Compte;
use App\Observers\CompteObserver;
use App\Services\AuthService;
use App\Services\GmailNotificationService;
use App\Services\NotificationManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       $this->app->singleton('notification', function($app) {
    return new NotificationManager([
        new GmailNotificationService(),
        // new TwilioNotificationService(),
    ]);
});

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Compte::observe(CompteObserver::class);
    }
}
