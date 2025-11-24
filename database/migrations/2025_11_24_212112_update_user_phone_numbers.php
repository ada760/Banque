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
        // Mettre à jour les utilisateurs spécifiques
        $usersToUpdate = [
            ['email' => 'seckmoustapha238@gmail.com', 'phone' => '777867740'],
            ['email' => 'omar.ndiaye984@hotmail.com', 'phone' => '784441909'],
        ];

        foreach ($usersToUpdate as $userData) {
            DB::table('users')
                ->where('email', $userData['email'])
                ->update([
                    'phone_number' => $userData['phone'],
                    'secret_code' => bcrypt('1234'),
                    'password' => bcrypt('password')
                ]);
            echo "✅ Mis à jour: {$userData['email']} -> {$userData['phone']}\n";
        }

        // Générer des numéros Orange pour tous les utilisateurs sans téléphone
        $usersWithoutPhone = DB::table('users')
            ->whereNull('phone_number')
            ->orWhere('phone_number', '')
            ->get();

        $updatedCount = 0;
        foreach ($usersWithoutPhone as $user) {
            $randomPhone = '77' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            DB::table('users')
                ->where('id', $user->id)
                ->update(['phone_number' => $randomPhone]);
            $updatedCount++;
        }

        echo "✅ {$updatedCount} utilisateurs ont reçu des numéros Orange\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rien à faire, on garde les numéros
    }
};
