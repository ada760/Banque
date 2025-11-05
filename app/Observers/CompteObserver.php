<?php

namespace App\Observers;

use App\Models\Compte;
use App\Services\NumeroCompteService;

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
