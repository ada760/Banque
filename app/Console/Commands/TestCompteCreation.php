<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CompteService;
use Illuminate\Support\Facades\Log;

class TestCompteCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:compte-creation {email : L\'adresse email pour le test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste la création d\'un compte et l\'envoi d\'email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Test de création de compte avec email: {$email}");

        $compteService = app(CompteService::class);

        $data = [
            'type' => 'epargne',
            'devise' => 'XOF',
            'status' => 'actif',
            'email' => $email,
            'telephone' => '123456789',
            'adresse' => 'Adresse de test',
            'cni' => '1234567890123',
            'titulaire' => 'Client de Test'
        ];

        try {
            $this->info("Création du compte en cours...");

            $result = $compteService->createCompte($data);

            $this->info("✅ Compte créé avec succès!");
            $this->table(
                ['Information', 'Valeur'],
                [
                    ['ID Compte', $result['compte']->id],
                    ['Numéro Compte', $result['compte']->num_compte],
                    ['Type', $result['compte']->type],
                    ['Devise', $result['compte']->devise],
                    ['Email Client', $result['user']->email],
                    ['Mot de passe généré', $result['generatedPassword'] ? 'Oui' : 'Non'],
                ]
            );

            $this->info("Vérifiez les logs pour voir si l'email a été envoyé:");
            $this->warn("tail -f storage/logs/laravel.log");
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la création du compte: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
