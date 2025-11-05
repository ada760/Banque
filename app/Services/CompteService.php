<?php


namespace App\Services;

use App\Models\Compte;
use Error;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompteService
{
    private UserService $userService;
    private ClientService $clientService;

    public function __construct(UserService $userService, ClientService $clientService)
    {
        $this->userService = $userService;
        $this->clientService = $clientService;
    }

    // public function createCompteWithClient(array $data): array
    // {
    //     return DB::transaction(function () use ($data) {
    //         [$user, $generatedPassword] = $this->userService->findOrCreate($data);
    //         $client = $this->clientService->findOrCreateClient($data, $user);

    //         $compte = Compte::create([
    //             'type' => $data['type'],
    //             'num_compte' => $data['num_compte'],
    //             'devise' => $data['devise'],
    //             'status' => $data['status'],
    //             'client_id' => $client->id,
    //         ]);

    //         return [
    //             'compte' => $compte,
    //             'client' => $client,
    //             'user' => $user,
    //             'generated_password' => $generatedPassword,
    //         ];
    //     });
    // }

    public function createCompte(array $data)
    {
        try {
            
            $result =  DB::transaction(function() use ($data) {
                [$user,$generatedPassword] = $this->userService->create($data);
                $client = $this->clientService->createClient($data,$user);
                $compte = Compte::create([
                    'client_id'=>$client->id,
                    'type' => $data['type'],
                    // 'num_compte' => $data['num_compte'],
                    'devise' => $data['devise'],
                    'status' => 'actif',

                ]);
                 // Après la transaction
                 Cache::put('generated_password_' . $compte->id, $generatedPassword, 60);

                return [
                'compte' => $compte,
                'client' => $client,
                'user' => $user,
                'generatedPassword' => $generatedPassword,

            ];

            
        });
       
        return $result;
        } catch (\Exception $e) {

            Log::error('Erreur lors de la transaction : ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
       
    }
}