<?php

namespace App\Observers;

use App\Facades\Notification;
use App\Models\Compte;
use App\Services\CompteService;
use App\Services\NumeroCompteService;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompteObserver
{
    private NumeroCompteService $numeroCompteService;
   
    
    public function __construct(NumeroCompteService $numeroCompteService)
    {
        $this->numeroCompteService = $numeroCompteService;
       
    }

    public function creating(Compte $compte)
    {
       $compte->num_compte =  $this->numeroCompteService->generate();
    }
   
    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        $password = Cache::get('password_' . $compte->id);

        var_dump($password);
        // die;
        $password = 'thierno';
        if ($password) {
            // Déclencher l'événement avec le mot de passe
            event(new \App\Events\CompteCreated($compte, $password));
        }
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        //
    }
}
