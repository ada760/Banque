<?php


namespace App\Services;

use App\Models\Compte;
use Illuminate\Support\Facades\DB;

class CompteService
{
    private UserService $userService;
    private ClientService $clientService;

    public function __construct(UserService $userService, ClientService $clientService)
    {
        $this->userService = $userService;
        $this->clientService = $clientService;
    }

    public function createCompteWithClient(array $data): array
    {
        return DB::transaction(function () use ($data) {
            [$user, $generatedPassword] = $this->userService->findOrCreate($data);
            $client = $this->clientService->findOrCreateClient($data, $user);

            $compte = Compte::create([
                'type' => $data['type'],
                'num_compte' => $data['num_compte'],
                'devise' => $data['devise'],
                'status' => $data['status'],
                'client_id' => $client->id,
            ]);

            return [
                'compte' => $compte,
                'client' => $client,
                'user' => $user,
                'generated_password' => $generatedPassword,
            ];
        });
    }
}