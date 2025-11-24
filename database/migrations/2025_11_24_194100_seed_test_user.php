<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Créer l'utilisateur de test Moustapha s'il n'existe pas
        $userExists = DB::table('users')
            ->where('phone_number', '772687847')
            ->exists();

        if (!$userExists) {
            $userId = (string) \Illuminate\Support\Str::uuid();
            DB::table('users')->insert([
                'id' => $userId,
                'titulaire' => 'Moustapha Seck',
                'email' => 'seckmoustapha238@gmail.com',
                'phone_number' => '772687847',
                'password' => bcrypt('password'), // Mot de passe par défaut
                'secret_code' => bcrypt('1234'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Créer le client associé
            $clientId = (string) \Illuminate\Support\Str::uuid();
            DB::table('clients')->insert([
                'id' => $clientId,
                'telephone' => '772687847',
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Créer un compte bancaire pour le client
            DB::table('comptes')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'num_compte' => 'C' . rand(100000000, 999999999),
                'client_id' => $clientId,
                'solde' => 100000.00, // Solde initial
                'devise' => 'XOF',
                'status' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            echo "✅ Utilisateur de test Moustapha créé avec succès\n";
        } else {
            echo "⚠️ Utilisateur de test Moustapha existe déjà\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('phone_number', '772687847')
            ->delete();
    }
};
