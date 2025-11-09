<?php


namespace App\Services;

use App\Models\Client;
use App\Models\User;

class ClientService
{

    /**
     * Retourne le client existant (par cni ou telephone) ou le crée.
     * Associe le user si nécessaire.
     */
    // public function findOrCreateClient(array $data, User $user): Client
    // {
    //     $client = Client::where('cni', $data['cni'])
    //         ->orWhere('telephone', $data['telephone'])
    //         ->first();

    //     if (! $client) {
    //         $client = Client::create([
    //             'user_id' => $user->id,
    //             'cni' => $data['cni'],
    //             'telephone' => $data['telephone'],
    //             'adresse' => $data['adresse'],
    //         ]);
    //     } else {
    //         if (! $client->user_id) {
    //             $client->user_id = $user->id;
    //             $client->save();
    //         }
    //     }

    //     return $client;
    // }

    public function createClient(array $data, User $user): Client|null
    {

        return Client::create([
            'user_id' => $user->id,
            'cni' => $data['cni'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'],
        ]);
    }

    /**
     * Trouve un client existant par CNI ou téléphone
     */
    public function findClientByCniOrPhone(string $cni, string $telephone): ?Client
    {
        return Client::where('cni', $cni)
            ->orWhere('telephone', $telephone)
            ->first();
    }
}
