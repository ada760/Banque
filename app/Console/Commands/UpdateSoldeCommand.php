<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateSoldeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'solde:update {name} {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mettre à jour le solde d\'un utilisateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $amount = $this->argument('amount');

        if ($name === 'check') {
            // Mode vérification des données
            $this->info("=== VÉRIFICATION DES DONNÉES ===");

            $users = User::all();
            $this->info("Utilisateurs trouvés: " . $users->count());

            foreach ($users as $user) {
                $this->line("User: {$user->titulaire} - Phone: {$user->phone_number} - Email: {$user->email}");
                if ($user->client) {
                    $this->line("  └─ Client: {$user->client->telephone}");
                    if ($user->client->compte) {
                        foreach ($user->client->compte as $compte) {
                            $this->line("     └─ Compte: {$compte->num_compte} - Solde: {$compte->solde} - Status: {$compte->status}");
                        }
                    }
                }
            }

            $clients = \App\Models\Client::all();
            $this->info("Clients trouvés: " . $clients->count());

            return 0;
        }

        // Trouver l'utilisateur par nom
        $user = User::where('titulaire', $name)->first();

        if (!$user) {
            $this->error("Utilisateur '{$name}' non trouvé.");
            return 1;
        }

        // Vérifier si c'est un client
        if (!$user->client) {
            $this->error("L'utilisateur '{$name}' n'est pas un client.");
            return 1;
        }

        // Récupérer le compte
        $compte = $user->client->compte()->first();

        if (!$compte) {
            $this->error("Aucun compte trouvé pour '{$name}'.");
            return 1;
        }

        // Mettre à jour le solde
        $ancienSolde = $compte->solde;
        $compte->update(['solde' => $amount]);

        $this->info("Solde de {$name} mis à jour : {$ancienSolde} → {$amount} FCFA");
        return 0;
    }
}
