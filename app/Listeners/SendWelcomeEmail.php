<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
            \App\Facades\Notification::send($email, "Votre mot de passe par défaut est : $password");
            \Illuminate\Support\Facades\Cache::forget('password_' . $event->compte->id);
        }
    }
}
