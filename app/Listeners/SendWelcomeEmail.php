<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {


        $email = $event->compte->client->user->email ?? null;
        $password = $event->password;



        if ($email && $password) {

            $message = "Bienvenue dans notre banque !\n\n";
            $message .= "Votre compte a été créé avec succès.\n";
            $message .= "Numéro de compte: " . $event->compte->num_compte . "\n";
            $message .= "Votre mot de passe par défaut est : $password\n\n";
            $message .= "Veuillez changer ce mot de passe lors de votre première connexion.";

            Notification::send($email, $message);

            // Nettoyer le cache
            Cache::forget('generated_password_' . $event->compte->id);

        } 
    }
}
