<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class OtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_TTL = 360; // 6 minutes
    private const MAX_ATTEMPTS = 3;
    private const RATE_LIMIT_SECONDS = 60; // 1 minute entre demandes

    /**
     * Génère et envoie un OTP pour un numéro de téléphone
     */
    public function requestOtp(string $phone): array
    {
        // Vérifier si le client existe
        $client = Client::where('telephone', $phone)->first();
        if (!$client) {
            throw new \Exception('Numéro de téléphone non trouvé dans notre système');
        }

        // Vérifier rate limiting
        if (!$this->canRequestOtp($client)) {
            throw new \Exception('Veuillez attendre avant de demander un nouveau code');
        }

        // Vérifier si compte bloqué
        if ($this->isBlocked($client)) {
            throw new \Exception('Compte temporairement bloqué. Réessayez plus tard.');
        }

        // Générer OTP
        $otp = $this->generateOtp();

        // Stocker OTP en cache (hashé)
        $this->storeOtp($phone, $otp);

        // Mettre à jour client
        $client->update([
            'last_otp_request' => now(),
            'otp_attempts' => 0 // Reset attempts
        ]);

        // Envoyer OTP par email (synchrone en production, asynchrone en développement)
        if (app()->environment('production')) {
            $this->sendOtpEmailSync($client->user->email, $otp);
        } else {
            $this->sendOtpEmail($client->user->email, $otp);
        }

        Log::info('OTP demandé', [
            'phone' => $phone,
            'client_id' => $client->id,
            'user_id' => $client->user_id
        ]);

        return [
            'message' => 'Code OTP envoyé par email',
            'expires_in' => self::OTP_TTL
        ];
    }

    /**
     * Vérifie un OTP
     */
    public function verifyOtp(string $phone, string $otp): bool
    {
        $client = Client::where('telephone', $phone)->first();
        if (!$client) {
            return false;
        }

        // Vérifier si bloqué
        if ($this->isBlocked($client)) {
            throw new \Exception('Compte temporairement bloqué');
        }

        $cacheKey = "otp:{$phone}";
        $storedData = Cache::get($cacheKey);

        if (!$storedData) {
            $this->incrementAttempts($client);
            return false;
        }

        // Vérifier OTP avec password_verify (plus approprié pour bcrypt)
        if (!password_verify($otp, $storedData['otp'])) {
            $this->incrementAttempts($client);
            return false;
        }

        // Vérifier expiration
        if (Carbon::now()->timestamp > $storedData['expires_at']) {
            $this->incrementAttempts($client);
            return false;
        }

        // OTP valide - supprimer du cache
        Cache::forget($cacheKey);

        // Reset attempts
        $client->update(['otp_attempts' => 0]);

        Log::info('OTP vérifié avec succès', [
            'phone' => $phone,
            'client_id' => $client->id
        ]);

        return true;
    }

    /**
     * Définit le code secret OM pour un client
     */
    public function setSecretCode(string $phone, string $secretCode): Client
    {
        $client = Client::where('telephone', $phone)->first();
        if (!$client) {
            throw new \Exception('Client non trouvé');
        }

        // Valider format code secret (4 chiffres)
        if (!preg_match('/^\d{4}$/', $secretCode)) {
            throw new \Exception('Le code secret doit contenir exactement 4 chiffres');
        }

        $client->update([
            'code_secret_om' => bcrypt($secretCode),
            'otp_attempts' => 0
        ]);

        Log::info('Code secret OM défini', [
            'client_id' => $client->id,
            'phone' => $phone
        ]);

        return $client;
    }

    /**
     * Vérifie le code secret OM
     */
    public function verifySecretCode(string $phone, string $secretCode): ?Client
    {
        $client = Client::where('telephone', $phone)->first();
        if (!$client || !$client->code_secret_om) {
            return null;
        }

        if (!hash_equals($client->code_secret_om, bcrypt($secretCode))) {
            return null;
        }

        return $client;
    }

    /**
     * Génère un OTP aléatoire
     */
    private function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Stocke l'OTP en cache (hashé)
     */
    private function storeOtp(string $phone, string $otp): void
    {
        $cacheKey = "otp:{$phone}";
        $data = [
            'otp' => bcrypt($otp),
            'expires_at' => Carbon::now()->addSeconds(self::OTP_TTL)->timestamp,
            'attempts' => 0
        ];

        Cache::put($cacheKey, $data, self::OTP_TTL);
    }

    /**
     * Envoie l'OTP par email (asynchrone avec queue)
     */
    private function sendOtpEmail(string $email, string $otp): void
    {
        try {
            Mail::raw("Votre code de vérification OM Pay est : {$otp}. Ce code expire dans 6 minutes.", function ($message) use ($email) {
                $message->to($email)
                        ->subject('Code de vérification OM Pay');
            });
        } catch (\Exception $e) {
            Log::error('Erreur envoi email OTP', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Erreur lors de l\'envoi du code par email');
        }
    }

    /**
     * Envoie l'OTP par email (synchrone pour production)
     */
    private function sendOtpEmailSync(string $email, string $otp): void
    {
        try {
            Mail::raw("Votre code de vérification OM Pay est : {$otp}. Ce code expire dans 6 minutes.", function ($message) use ($email) {
                $message->to($email)
                        ->subject('Code de vérification OM Pay');
            })->send(); // Force l'envoi synchrone
        } catch (\Exception $e) {
            Log::error('Erreur envoi email OTP (sync)', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Erreur lors de l\'envoi du code par email');
        }
    }

    /**
     * Vérifie si on peut demander un nouvel OTP
     */
    private function canRequestOtp(Client $client): bool
    {
        if (!$client->last_otp_request) {
            return true;
        }

        return $client->last_otp_request->addSeconds(self::RATE_LIMIT_SECONDS)->isPast();
    }

    /**
     * Vérifie si le client est bloqué
     */
    private function isBlocked(Client $client): bool
    {
        return $client->blocked_until && $client->blocked_until->isFuture();
    }

    /**
     * Incrémente les tentatives et bloque si nécessaire
     */
    private function incrementAttempts(Client $client): void
    {
        $attempts = $client->otp_attempts + 1;

        $updateData = ['otp_attempts' => $attempts];

        if ($attempts >= self::MAX_ATTEMPTS) {
            // Bloquer pour 15 minutes
            $updateData['blocked_until'] = now()->addMinutes(15);
            $updateData['otp_attempts'] = 0; // Reset après blocage
        }

        $client->update($updateData);

        Log::warning('Tentative OTP échouée', [
            'client_id' => $client->id,
            'attempts' => $attempts
        ]);
    }
}