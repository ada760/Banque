<?php

namespace App\Policies;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComptePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin can view all accounts, client can view their own accounts
        return $user->admin || $user->client;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Compte $compte): bool
    {
        // Admin can view any account, client can only view their own accounts
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins can create accounts client cant just see own accounts but he can't create account
        return $user->admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Compte $compte): bool
    {
        // Admin can update any account, client can only update their own accounts
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Compte $compte): bool
    {
        // Admin can delete any account, client can only delete their own accounts
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Compte $compte): bool
    {
        // Admin can restore any account, client can only restore their own accounts
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Compte $compte): bool
    {
        // Admin can force delete any account, client can only force delete their own accounts
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }
}
