<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
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
     * Normalise le numéro de téléphone (supprime +221 si présent)
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/^\+221/', '', $phone);
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
    public function verifyOtp(string $phone, string $otp): ?User
    {
        return $this->otpService->verifyOtp($phone, $otp);
    }

    /**
     * Étape 3: Définition du code secret (première fois)
     */
    public function setSecretCode(string $phone, string $secretCode): array
    {
        $user = User::where('phone_number', $phone)->first();
        if (!$user) {
            throw new \Exception('Utilisateur non trouvé');
        }

        // Vérifier si l'utilisateur a déjà un code secret défini
        if (!empty($user->secret_code)) {
            // Si le code fourni correspond au code existant, permettre la connexion
            if (Hash::check($secretCode, $user->secret_code)) {
                // Générer token JWT pour connexion normale
                $token = $this->generateToken($user);

                return [
                    'message' => 'Connexion réussie avec code secret existant',
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user,
                    'is_first_login' => false
                ];
            } else {
                throw new \Exception('Un code secret est déjà défini pour ce numéro. Utilisez la connexion normale avec le bon code.');
            }
        }

        // Définir le code secret via OtpService (première fois)
        $user = $this->otpService->setSecretCode($phone, $secretCode);

        // Générer token JWT
        $token = $this->generateToken($user);

        return [
            'message' => 'Code secret défini avec succès',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'is_first_login' => true
        ];
    }

    /**
     * Authentification avec code secret (connexions suivantes)
     */
    public function loginWithSecretCode(string $phone, string $secretCode): array
    {
        $user = $this->otpService->verifySecretCode($phone, $secretCode);

        if (!$user) {
            throw new \Exception('Code secret incorrect');
        }

        // Vérifier que l'utilisateur a un client avec un compte actif
        $client = $user->client;
        if (!$client) {
            throw new \Exception('Aucun client associé trouvé');
        }

        $compteActif = $client->compte->where('status', 'actif')->first();
        if (!$compteActif) {
            throw new \Exception('Aucun compte actif trouvé');
        }

        $token = $this->generateToken($user);

        return [
            'message' => 'Connexion réussie',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'client' => $client,
            'compte_actif' => $compteActif,
            'is_first_login' => false
        ];
    }

    /**
     * Vérifie si un utilisateur a déjà un code secret défini
     */
    public function hasSecretCode(string $phone): bool
    {
        $user = User::where('phone_number', $phone)->first();
        return $user && !empty($user->secret_code);
    }

    /**
     * Génère un token JWT pour l'utilisateur
     */
    private function generateToken(User $user): string
    {
        // Utilisation de Passport (comme dans AuthService existant)
        return $user->createToken('OM Pay Token')->accessToken;
    }

    /**
     * Génère un token JWT pour l'utilisateur (méthode publique)
     */
    public function generateTokenForUser(User $user): string
    {
        return $this->generateToken($user);
    }

    /**
     * Vérifie si l'OTP a été vérifié pour un numéro de téléphone
     */
    public function isOtpVerified(string $phone): bool
    {
        return $this->otpService->isOtpVerified($phone);
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
        // Révoquer tous les tokens de l'utilisateur (Passport)
        if (Auth::check()) {
            Auth::user()->tokens()->delete();
        }
    }

    /**
     * Récupère l'utilisateur actuellement authentifié
     */
    public function getCurrentUser(): ?User
    {
        return Auth::user();
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