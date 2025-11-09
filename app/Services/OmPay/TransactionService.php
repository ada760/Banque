<?php

namespace App\Services\OmPay;

use App\Events\OmPay\TransactionCreated;
use App\Models\OmPay\Transaction;
use App\Repositories\OmPay\TransactionRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService extends BaseService
{
    protected TransactionRepository $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function createTransaction(array $data)
    {
        // Validation des données
        $this->validateTransactionData($data);

        try {
            // Transaction MongoDB (pas de rollback automatique comme SQL)
            $transaction = $this->transactionRepository->create([
                'user_id' => $data['user_id'],
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'status' => 'pending',
                'reference' => $data['reference'] ?? null,
                'merchant_id' => $data['merchant_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'qr_metadata' => $data['qr_metadata'] ?? null,
                'fee' => $this->calculateFee($data['amount'], $data['type']),
                'description' => $data['description'] ?? null,
                'transaction_date' => now(),
            ]);

            // Déclencher l'événement
            event(new TransactionCreated($transaction));

            // Simuler le traitement (dans un vrai système, cela serait asynchrone)
            $this->processTransaction($transaction);

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Erreur création transaction OM Pay', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getUserTransactions($userId, $limit = 10)
    {
        return $this->transactionRepository->getByUser($userId, $limit);
    }

    public function getTransactionStats($userId)
    {
        return [
            'total_transactions' => $this->transactionRepository->getByUser($userId)->count(),
            'total_amount' => $this->transactionRepository->getTotalAmountByUser($userId),
            'successful_transactions' => $this->transactionRepository->getByUser($userId)->where('status', 'success')->count(),
        ];
    }

    private function validateTransactionData(array $data): void
    {
        $rules = [
            'user_id' => 'required|integer',
            'type' => 'required|in:transfer,payment,recharge',
            'amount' => 'required|numeric|min:100',
        ];

        if ($data['type'] === 'transfer') {
            $rules['recipient_phone'] = 'required|string';
        }

        // Validation manuelle simplifiée
        foreach ($rules as $field => $rule) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ {$field} est requis");
            }
        }

        // Validation du solde disponible
        $fee = $this->calculateFee($data['amount'], $data['type']);
        $this->validateSufficientBalance($data['user_id'], $data['amount'], $fee);

        // Validation métier : vérifier que le destinataire existe pour les transferts
        if ($data['type'] === 'transfer') {
            $this->validateRecipientExists($data['recipient_phone'], $data['user_id']);
        }
    }

    private function validateSufficientBalance($userId, $amount, $fee): void
    {
        // Récupérer le client et son compte actif
        $client = \App\Models\Client::where('user_id', $userId)->first();

        if (!$client) {
            throw new \InvalidArgumentException("Client non trouvé");
        }

        $compte = $client->compte->where('status', 'actif')->first();

        if (!$compte) {
            throw new \InvalidArgumentException("Aucun compte actif trouvé pour ce client");
        }

        $totalAmount = $amount + $fee;

        if ($compte->solde < $totalAmount) {
            throw new \InvalidArgumentException(
                "Solde insuffisant. Solde disponible: {$compte->solde} XOF, montant requis: {$totalAmount} XOF (dont {$fee} XOF de frais)"
            );
        }
    }

    private function validateRecipientExists(string $recipientPhone, $senderUserId): void
    {
        // Vérifier que le numéro de téléphone appartient à un client existant
        $recipientClient = \App\Models\Client::where('telephone', $recipientPhone)->first();

        if (!$recipientClient) {
            throw new \InvalidArgumentException("Le numéro de téléphone destinataire n'existe pas dans notre système");
        }

        // Vérifier que le destinataire a un compte actif
        if (!$recipientClient->compte || $recipientClient->compte->where('status', 'actif')->isEmpty()) {
            throw new \InvalidArgumentException("Le destinataire n'a pas de compte bancaire actif");
        }

        // Vérifier que ce n'est pas un transfert vers soi-même
        if ($recipientClient->user_id === $senderUserId) {
            throw new \InvalidArgumentException("Vous ne pouvez pas transférer de l'argent vers votre propre compte");
        }
    }

    private function calculateFee(float $amount, string $type): float
    {
        // Logique de calcul des frais (simplifiée)
        $feePercentage = match($type) {
            'transfer' => 0.005, // 0.5%
            'payment' => 0.01,   // 1%
            'recharge' => 0.0,   // Gratuit
            default => 0.0
        };

        return $amount * $feePercentage;
    }

    private function processTransaction(Transaction $transaction): void
    {
        // Simulation du traitement (dans un vrai système : appel API externe, vérification solde, etc.)
        // Ici, on marque simplement comme succès après un délai simulé
        $transaction->update(['status' => 'success']);

        // Mettre à jour le solde du compte après transaction réussie
        $this->updateBalance($transaction);
    }

    private function updateBalance(Transaction $transaction): void
    {
        // Récupérer le client et son compte actif
        $client = \App\Models\Client::where('user_id', $transaction->user_id)->first();

        if (!$client) {
            Log::error('Client non trouvé lors de la mise à jour du solde', ['user_id' => $transaction->user_id]);
            return;
        }

        $compte = $client->compte->where('status', 'actif')->first();

        if (!$compte) {
            Log::error('Compte actif non trouvé lors de la mise à jour du solde', ['user_id' => $transaction->user_id]);
            return;
        }

        // Logique différente selon le type de transaction
        if ($transaction->type === 'recharge') {
            // Pour les recharges : AJOUTER au solde (pas de frais)
            $compte->increment('solde', $transaction->amount);

            Log::info('Solde rechargé après transaction', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'type' => 'recharge',
                'new_balance' => $compte->fresh()->solde
            ]);
        } else {
            // Pour transferts et paiements : DÉBITER du solde (montant + frais)
            $totalDebit = $transaction->amount + $transaction->fee;
            $compte->decrement('solde', $totalDebit);

            Log::info('Solde débité après transaction', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'fee' => $transaction->fee,
                'total_debit' => $totalDebit,
                'type' => $transaction->type,
                'new_balance' => $compte->fresh()->solde
            ]);
        }

        // Pour les transferts, créditer le destinataire
        if ($transaction->type === 'transfer' && $transaction->recipient_phone) {
            $this->creditRecipient($transaction);
        }
    }

    private function creditRecipient(Transaction $transaction): void
    {
        // Récupérer le destinataire
        $recipientClient = \App\Models\Client::where('telephone', $transaction->recipient_phone)->first();

        if (!$recipientClient) {
            Log::error('Destinataire non trouvé lors du crédit', ['recipient_phone' => $transaction->recipient_phone]);
            return;
        }

        $recipientCompte = $recipientClient->compte->where('status', 'actif')->first();

        if (!$recipientCompte) {
            Log::error('Compte actif du destinataire non trouvé lors du crédit', ['recipient_phone' => $transaction->recipient_phone]);
            return;
        }

        // Créditer le montant (sans frais) au destinataire
        $recipientCompte->increment('solde', $transaction->amount);

        Log::info('Destinataire crédité après transfert', [
            'transaction_id' => $transaction->id,
            'recipient_phone' => $transaction->recipient_phone,
            'amount' => $transaction->amount,
            'recipient_new_balance' => $recipientCompte->fresh()->solde
        ]);
    }
}