<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer l'utilisateur admin
        $user = \App\Models\User::create([
            'id' => fake()->uuid(),
            'titulaire' => 'Administrateur Principal',
            'email' => 'admin@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Créer l'admin associé
        \App\Models\Admin::create([
            'id' => fake()->uuid(),
            'user_id' => $user->id,
        ]);

        // Créer 75 clients sénégalais réalistes
        $this->call([
            ClientSeeder::class,
        ]);

        // Créer les comptes associés
        $this->call([
            CompteSeeder::class,
        ]);
    }
}
