<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
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
        // Vérifier si l'utilisateur existe
        $user = User::where('phone_number', $phone)->first();
        if (!$user) {
            throw new \Exception('Numéro de téléphone non trouvé dans notre système');
        }

        // Vérifier rate limiting
        if (!$this->canRequestOtp($user)) {
            throw new \Exception('Veuillez attendre avant de demander un nouveau code');
        }

        // Vérifier si compte bloqué
        if ($this->isBlocked($user)) {
            throw new \Exception('Compte temporairement bloqué. Réessayez plus tard.');
        }

        // Générer OTP
        $otp = $this->generateOtp();

        // Stocker OTP en cache (hashé)
        $this->storeOtp($phone, $otp);

        // Stocker temporairement le numéro de téléphone pour la vérification
        Cache::put('last_otp_phone', $phone, self::OTP_TTL);

        // Mettre à jour user
        $user->update([
            'last_otp_request' => now(),
            'otp_attempts' => 0 // Reset attempts
        ]);

        // Envoyer l'OTP par email (MOCK - log seulement)
        $this->sendOtpEmailSync($user->email, $otp);

        Log::info('OTP demandé (MOCK)', [
            'phone' => $phone,
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return [
            'message' => 'Code OTP généré - Vérifiez les logs pour le code',
            'expires_in' => self::OTP_TTL
        ];
    }

    /**
     * Vérifie un OTP pour un numéro de téléphone spécifique
     */
    public function verifyOtp(string $phone, string $otp): ?User
    {
        $cacheKey = "otp:{$phone}";
        $storedData = Cache::get($cacheKey);

        if (!$storedData || !isset($storedData['otp'])) {
            return null;
        }

        // Vérifier OTP avec password_verify
        if (!password_verify($otp, $storedData['otp'])) {
            $this->incrementAttempts(User::where('phone_number', $phone)->first());
            return null;
        }

        // Vérifier expiration
        if (Carbon::now()->timestamp > $storedData['expires_at']) {
            return null; // OTP expiré
        }

        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return null;
        }

        // Vérifier si bloqué
        if ($this->isBlocked($user)) {
            throw new \Exception('Compte temporairement bloqué');
        }

        // OTP valide - marquer comme vérifié et supprimer du cache
        Cache::put("otp_verified:{$phone}", true, 300); // 5 minutes
        Cache::forget($cacheKey);

        // Reset attempts
        $user->update(['otp_attempts' => 0]);

        Log::info('OTP vérifié avec succès', [
            'phone' => $phone,
            'user_id' => $user->id
        ]);

        return $user;
    }

    /**
     * Définit le code secret pour un utilisateur
     */
    public function setSecretCode(string $phone, string $secretCode): User
    {
        $user = User::where('phone_number', $phone)->first();
        if (!$user) {
            throw new \Exception('Utilisateur non trouvé');
        }

        // Valider format code secret (4 chiffres)
        if (!preg_match('/^\d{4}$/', $secretCode)) {
            throw new \Exception('Le code secret doit contenir exactement 4 chiffres');
        }

        $user->update([
            'secret_code' => $secretCode,
            'otp_attempts' => 0
        ]);

        Log::info('Code secret défini', [
            'user_id' => $user->id,
            'phone' => $phone
        ]);

        return $user;
    }

    /**
     * Vérifie si l'OTP a été vérifié pour un numéro de téléphone
     */
    public function isOtpVerified(string $phone): bool
    {
        return Cache::has("otp_verified:{$phone}");
    }

    /**
     * Vérifie le code secret
     */
    public function verifySecretCode(string $phone, string $secretCode): ?User
    {
        $user = User::where('phone_number', $phone)->first();
        if (!$user || !$user->secret_code) {
            return null;
        }

        if (!Hash::check($secretCode, $user->secret_code)) {
            return null;
        }

        return $user;
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
     * Envoie l'OTP par email (synchrone) - MOCK pour les tests
     */
    private function sendOtpEmailSync(string $email, string $otp): void
    {
        // MOCK: Ne pas envoyer d'email réel, juste logger l'OTP
        Log::warning('🎯 OTP POUR TESTS - CODE À UTILISER', [
            'email' => $email,
            'otp_code' => $otp,
            'message' => 'UTILISEZ CE CODE POUR LES TESTS',
            'instructions' => 'Copiez ce code OTP pour tester la vérification'
        ]);

        // Simuler un envoi réussi
        Log::info('Mock email OTP envoyé (sync)', ['email' => $email]);

        // Ne pas lever d'exception pour permettre les tests
        return;
    }

    /**
     * Vérifie si on peut demander un nouvel OTP
     */
    private function canRequestOtp(User $user): bool
    {
        if (!$user->last_otp_request) {
            return true;
        }

        return $user->last_otp_request->addSeconds(self::RATE_LIMIT_SECONDS)->isPast();
    }

    /**
     * Vérifie si l'utilisateur est bloqué
     */
    private function isBlocked(User $user): bool
    {
        return $user->blocked_until && $user->blocked_until->isFuture();
    }

    /**
     * Incrémente les tentatives et bloque si nécessaire
     */
    private function incrementAttempts(User $user): void
    {
        $attempts = $user->otp_attempts + 1;

        $updateData = ['otp_attempts' => $attempts];

        if ($attempts >= self::MAX_ATTEMPTS) {
            // Bloquer pour 15 minutes
            $updateData['blocked_until'] = now()->addMinutes(15);
            $updateData['otp_attempts'] = 0; // Reset après blocage
        }

        $user->update($updateData);

        Log::warning('Tentative OTP échouée', [
            'user_id' => $user->id,
            'attempts' => $attempts
        ]);
    }
}