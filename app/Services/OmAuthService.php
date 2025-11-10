<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OmAuthService
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Étape 1: Demande d'OTP pour première connexion
     */
    public function requestOtp(string $phone): array
    {
        return $this->otpService->requestOtp($phone);
    }

    /**
     * Étape 2: Vérification OTP
     */
    public function verifyOtp(string $phone, string $otp): bool
    {
        return $this->otpService->verifyOtp($phone, $otp);
    }

    /**
     * Étape 3: Définition du code secret OM (première fois)
     */
    public function setSecretCode(string $phone, string $secretCode): array
    {
        $client = Client::where('telephone', $phone)->first();
        if (!$client) {
            throw new \Exception('Client non trouvé');
        }

        // Vérifier si le client a déjà un code secret défini
        if (!empty($client->code_secret_om)) {
            // Si le code fourni correspond au code existant, permettre la connexion
            if (password_verify($secretCode, $client->code_secret_om)) {
                // Générer token JWT pour connexion normale
                $token = $this->generateToken($client);

                return [
                    'message' => 'Connexion réussie avec code secret existant',
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'client' => $client,
                    'is_first_login' => false
                ];
            } else {
                throw new \Exception('Un code secret OM Pay est déjà défini pour ce numéro. Utilisez la connexion normale avec le bon code.');
            }
        }

        // Définir le code secret via OtpService (première fois)
        $client = $this->otpService->setSecretCode($phone, $secretCode);

        // Générer token JWT
        $token = $this->generateToken($client);

        return [
            'message' => 'Code secret défini avec succès',
            'token' => $token,
            'token_type' => 'Bearer',
            'client' => $client,
            'is_first_login' => true
        ];
    }

    /**
     * Authentification avec code secret OM (connexions suivantes)
     */
    public function loginWithSecretCode(string $phone, string $secretCode): array
    {
        $client = $this->otpService->verifySecretCode($phone, $secretCode);

        if (!$client) {
            throw new \Exception('Code secret incorrect');
        }

        // Vérifier que le client a un compte actif
        $compteActif = $client->compte->where('status', 'actif')->first();
        if (!$compteActif) {
            throw new \Exception('Aucun compte actif trouvé');
        }

        $token = $this->generateToken($client);

        return [
            'message' => 'Connexion réussie',
            'token' => $token,
            'token_type' => 'Bearer',
            'client' => $client,
            'compte_actif' => $compteActif,
            'is_first_login' => false
        ];
    }

    /**
     * Vérifie si un client a déjà un code secret défini
     */
    public function hasSecretCode(string $phone): bool
    {
        $client = Client::where('telephone', $phone)->first();
        return $client && !empty($client->code_secret_om);
    }

    /**
     * Génère un token JWT pour le client
     */
    private function generateToken(Client $client): string
    {
        // Utilisation de Passport (comme dans AuthService existant)
        return $client->user->createToken('OM Pay Token')->accessToken;
    }

    /**
     * Rafraîchit le token (Passport)
     */
    public function refreshToken(): array
    {
        // Avec Passport, les tokens sont long-lived, pas besoin de refresh
        throw new \Exception('Les tokens Passport n\'ont pas besoin d\'être rafraîchis');
    }

    /**
     * Déconnexion (révocation du token)
     */
    public function logout(): void
    {
        // Révoquer le token actuel (Passport)
        if (Auth::check()) {
            Auth::user()->currentAccessToken()->delete();
        }
    }

    /**
     * Récupère le client actuellement authentifié
     */
    public function getCurrentClient(): ?Client
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return $user->client;
    }
}