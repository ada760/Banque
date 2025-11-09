<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Compte;
use App\Models\OmPay\Transaction;
use App\Policies\ComptePolicy;
use App\Policies\OmPayPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Compte::class => ComptePolicy::class,
        Transaction::class => OmPayPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
