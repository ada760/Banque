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
        // Vérifier si l'utilisateur existe déjà (par email ou téléphone)
        $userExists = DB::table('users')
            ->where('email', 'seckmoustapha238@gmail.com')
            ->orWhere('phone_number', '772687847')
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

            echo "✅ Utilisateur Moustapha créé\n";
        } else {
            // Vérifier si l'utilisateur existant a les champs requis
            $user = DB::table('users')
                ->where('email', 'seckmoustapha238@gmail.com')
                ->orWhere('phone_number', '772687847')
                ->first();

            $userId = $user->id;

            // Mettre à jour l'utilisateur s'il manque des champs
            $needsUpdate = false;
            $updateData = [];

            if (empty($user->phone_number)) {
                $updateData['phone_number'] = '772687847';
                $needsUpdate = true;
            }

            if (empty($user->secret_code)) {
                $updateData['secret_code'] = bcrypt('1234');
                $needsUpdate = true;
            }

            if (empty($user->password)) {
                $updateData['password'] = bcrypt('password');
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                DB::table('users')->where('id', $userId)->update($updateData);
                echo "✅ Utilisateur Moustapha mis à jour\n";
            } else {
                echo "⚠️ Utilisateur Moustapha existe déjà (complet)\n";
            }
        }

        // Vérifier et créer le client si nécessaire
        $clientExists = DB::table('clients')
            ->where('user_id', $userId)
            ->exists();

        if (!$clientExists) {
            $clientId = (string) \Illuminate\Support\Str::uuid();
            DB::table('clients')->insert([
                'id' => $clientId,
                'telephone' => '772687847',
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "✅ Client Moustapha créé\n";
        } else {
            $client = DB::table('clients')->where('user_id', $userId)->first();
            $clientId = $client->id;
            echo "⚠️ Client Moustapha existe déjà\n";
        }

        // Vérifier et créer le compte si nécessaire
        $compteExists = DB::table('comptes')
            ->where('client_id', $clientId)
            ->exists();

        if (!$compteExists) {
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
            echo "✅ Compte bancaire Moustapha créé\n";
        } else {
            echo "⚠️ Compte bancaire Moustapha existe déjà\n";
        }

        // Mettre à jour des utilisateurs spécifiques avec des numéros de téléphone
        $usersToUpdate = [
            ['email' => 'seckmoustapha238@gmail.com', 'phone' => '777867740'],
            ['email' => 'omar.ndiaye984@hotmail.com', 'phone' => '784441909'],
        ];

        foreach ($usersToUpdate as $userData) {
            $updated = DB::table('users')
                ->where('email', $userData['email'])
                ->update(['phone_number' => $userData['phone']]);

            if ($updated) {
                echo "✅ Numéro de téléphone mis à jour pour {$userData['email']}: {$userData['phone']}\n";
            }
        }

        // Générer des numéros Orange (77xxxxxxxx) pour tous les utilisateurs sans téléphone
        $usersWithoutPhone = DB::table('users')
            ->whereNull('phone_number')
            ->orWhere('phone_number', '')
            ->get();

        $updatedCount = 0;
        foreach ($usersWithoutPhone as $user) {
            // Générer un numéro Orange aléatoire commençant par 77
            $randomPhone = '77' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);

            DB::table('users')
                ->where('id', $user->id)
                ->update(['phone_number' => $randomPhone]);

            $updatedCount++;
        }

        if ($updatedCount > 0) {
            echo "✅ {$updatedCount} utilisateurs mis à jour avec des numéros Orange aléatoires\n";
        }

        echo "✅ Migration terminée avec succès\n";
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
