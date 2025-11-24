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
            $userId = DB::table('users')->insertGetId([
                'titulaire' => 'Moustapha Seck',
                'email' => 'seckmoustapha238@gmail.com',
                'phone_number' => '772687847',
                'secret_code' => bcrypt('1234'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Créer le client associé
            DB::table('clients')->insert([
                'telephone' => '772687847',
                'user_id' => $userId,
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
