<?php

namespace App\Policies;

use App\Models\OmPay\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OmPayPolicy
{
    /**
     * Détermine si l'utilisateur peut créer une transaction OM Pay
     */
    public function createTransaction(User $user): bool
    {
        // Seuls les clients avec un compte bancaire actif peuvent faire des transactions
        return $user->client && $user->client->compte->where('status', 'actif')->isNotEmpty();
    }

    /**
     * Détermine si l'utilisateur peut voir ses propres transactions
     */
    public function viewOwnTransactions(User $user): bool
    {
        return $user->client !== null; // Tout client peut voir ses transactions
    }

    /**
     * Détermine si l'utilisateur peut voir toutes les transactions (admin)
     */
    public function viewAllTransactions(User $user): bool
    {
        return $user->admin; // Admin peut voir toutes les transactions
    }

    /**
     * Détermine si l'utilisateur peut voir les statistiques
     */
    public function viewStats(User $user): bool
    {
        return $user->client || $user->admin;
    }
}