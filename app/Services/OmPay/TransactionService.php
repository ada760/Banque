<?php

namespace App\Services\OmPay;

use App\Events\OmPay\TransactionCreated;
use App\Models\OmPay\Transaction;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService extends BaseService
{
    // Repository supprimé - utilisation directe d'Eloquent

    public function createTransaction(array $data)
    {
        // Validation des données
        $this->validateTransactionData($data);

        DB::beginTransaction();

        try {
            // Créer la transaction avec transaction PostgreSQL
            $transaction = Transaction::create([
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

            // Traiter la transaction (mise à jour du solde)
            $this->processTransaction($transaction);

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création transaction OM Pay', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getUserTransactions($userId, $limit = 10)
    {
        return Transaction::where('user_id', $userId)
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getFilteredTransactions($userId, array $filters = [], $perPage = 10, $page = 1)
    {
        // Construire la requête de base
        $query = Transaction::where('user_id', $userId);

        // Appliquer les filtres
        $appliedFilters = [];

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
            $appliedFilters[] = 'type';
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
            $appliedFilters[] = 'status';
        }

        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from'] . ' 00:00:00');
            $appliedFilters[] = 'date_from';
        }

        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to'] . ' 23:59:59');
            $appliedFilters[] = 'date_to';
        }

        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
            $appliedFilters[] = 'min_amount';
        }

        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
            $appliedFilters[] = 'max_amount';
        }

        if (!empty($filters['search'])) {
            $query->where('description', 'like', '%' . $filters['search'] . '%');
            $appliedFilters[] = 'search';
        }

        // Trier par date décroissante
        $query->orderBy('transaction_date', 'desc');

        // Pagination
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
            'filters' => array_merge(['applied' => $appliedFilters], $filters)
        ];
    }

    public function getTransactionStats($userId)
    {
        $transactions = Transaction::where('user_id', $userId);

        return [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'successful_transactions' => (clone $transactions)->where('status', 'success')->count(),
            'failed_transactions' => (clone $transactions)->where('status', 'failed')->count(),
            'pending_transactions' => (clone $transactions)->where('status', 'pending')->count(),
            'average_transaction' => $this->calculateAverageTransaction($userId),
            'monthly_stats' => $this->getMonthlyStats($userId),
        ];
    }

    private function calculateAverageTransaction($userId)
    {
        $transactions = Transaction::where('user_id', $userId)->get();
        $count = $transactions->count();
        return $count > 0 ? $transactions->sum('amount') / $count : 0;
    }

    private function getMonthlyStats($userId)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $monthlyTransactions = Transaction::where('user_id', $userId)
            ->whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->get();

        return [
            'current_month' => [
                'count' => $monthlyTransactions->count(),
                'amount' => $monthlyTransactions->sum('amount')
            ]
        ];
    }

    private function validateTransactionData(array $data): void
    {
        $rules = [
            'user_id' => 'required|string', // UUID format
            'type' => 'required|in:transfer,payment',
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
            \Illuminate\Support\Facades\Log::error('Client not found for user_id', ['user_id' => $userId]);
            throw new \InvalidArgumentException("Client non trouvé pour user_id: {$userId}");
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

        // Pour les transferts, créditer le destinataire (client)
        if ($transaction->type === 'transfer' && $transaction->recipient_phone) {
            $this->creditRecipient($transaction);
        }

        // Pour les paiements, créditer le marchand ou service
        // TODO: Implémenter une fois les modèles Merchant/Service créés en PostgreSQL
        if ($transaction->type === 'payment') {
            Log::info('Paiement effectué - Crédit marchand/service à implémenter', [
                'transaction_id' => $transaction->id,
                'merchant_id' => $transaction->merchant_id,
                'service_id' => $transaction->service_id,
            ]);
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