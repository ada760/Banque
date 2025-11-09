<?php

namespace App\Events;

use App\Models\Compte;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Compte $compte;
    public string|null $password;  // Changement ici : ajout de |null

    /**
     * Create a new event instance.
     */
    public function __construct(Compte $compte, string|null $password)
    {
        $this->compte = $compte;
        $this->password = $password;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
