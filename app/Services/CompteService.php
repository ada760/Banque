<?php

namespace App\Services;

use App\Models\Compte;
use App\Events\CompteCreated;
use App\Repositories\CompteRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompteService extends BaseService
{
    private UserService $userService;
    private ClientService $clientService;



    public function __construct(
        CompteRepository $repository,
        UserService $userService,
        ClientService $clientService
    ) {
        parent::__construct($repository); 
        $this->userService = $userService;
        $this->clientService = $clientService;
    }

    public function createCompte(array $data)
    {
        // Validation des données requises
        $this->validateRequiredData($data);

        try {
            return DB::transaction(function () use ($data) {
                // Vérifier si le client existe déjà
                $existingClient = $this->clientService->findClientByCniOrPhone(
                    $data['cni'],
                    $data['telephone']
                );

                if ($existingClient) {
                    // Client existe - créer juste un nouveau compte
                    return $this->createAccountForExistingClient($existingClient, $data);
                } else {
                    // Nouveau client - créer user, client et compte
                    return $this->createCompteForNewClient($data);
                }
            });
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte : ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Valide les données requises
     */
    private function validateRequiredData(array $data): void
    {
        $required = ['cni', 'telephone', 'type', 'devise'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ {$field} est requis");
            }
        }
    }

    /**
     * Crée un compte pour un client existant
     */
    private function createAccountForExistingClient($client, array $data): array
    {
        $compte = Compte::create([
            'client_id' => $client->id,
            'type' => $data['type'],
            'devise' => $data['devise'],
            'status' => 'actif',
        ]);

        // Pas de nouveau mot de passe généré pour un client existant
        event(new CompteCreated($compte, null));

        return [
            'compte' => $compte,
            'client' => $client,
            'user' => $client->user,
            'generatedPassword' => null,
            'isNewClient' => false,
            'message' => 'Nouveau compte ajouté pour le client existant'
        ];
    }

    /**
     * Crée un compte pour un nouveau client
     */
    private function createCompteForNewClient(array $data): array
    {
        [$user, $generatedPassword] = $this->userService->create($data);
        $client = $this->clientService->createClient($data, $user);

        $compte = Compte::create([
            'client_id' => $client->id,
            'type' => $data['type'],
            'devise' => $data['devise'],
            'status' => 'actif',
        ]);

        Cache::put('generated_password_' . $compte->id, $generatedPassword, 60);
        event(new CompteCreated($compte, $generatedPassword));

        return [
            'compte' => $compte,
            'client' => $client,
            'user' => $user,
            'generatedPassword' => $generatedPassword,
            'isNewClient' => true,
            'message' => 'Nouveau client et compte créés avec succès'
        ];
    }
}
