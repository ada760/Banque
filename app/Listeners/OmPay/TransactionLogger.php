<?php

namespace App\Listeners\OmPay;

use App\Events\OmPay\TransactionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TransactionLogger implements ShouldQueue
{
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;

        // Log détaillé de la transaction
        Log::info('Nouvelle transaction OM Pay créée', [
            'transaction_id' => $transaction->_id, // MongoDB ObjectId
            'user_id' => $transaction->user_id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
            'recipient_phone' => $transaction->recipient_phone,
            'fee' => $transaction->fee,
            'created_at' => $transaction->created_at,
        ]);

        // Ici, vous pourriez ajouter :
        // - Envoi de notification push
        // - Mise à jour de statistiques en cache
        // - Intégration avec un système de monitoring
        // - Envoi d'email de confirmation
    }
}