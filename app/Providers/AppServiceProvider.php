<?php

namespace App\Providers;

use App\Models\Compte;
use App\Observers\CompteObserver;
use App\Services\AuthService;
use Illuminate\Support\ServiceProvider;

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
        Compte::observe(CompteObserver::class);
    }
}
