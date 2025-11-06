<?php

namespace App\Observers;

use App\Events\CompteCreated;
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
    private UserService $user_service;


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

        $password = Cache::get('generated_password_' . $compte->id);

        Log::info("Mot de passe récupéré du cache", [
            'compte_id' => $compte->id,
            'cache_key' => 'generated_password_' . $compte->id,
            'password_found' => !empty($password),
            'password_length' => $password ? strlen($password) : 0
        ]);

        if ($password) {
            Log::info("Mot de passe trouvé dans le cache pour le compte ID: " . $compte->id . ", l'événement peut déjà avoir été dispatché par le service.");
        } else {
            Log::warning("Aucun mot de passe trouvé dans le cache pour le compte ID: " . $compte->id . ". Si le service dispatch l'événement, c'est attendu (pas d'action nécessaire ici).");
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
