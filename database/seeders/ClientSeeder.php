<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un client spécifique pour les tests
        Client::factory()->create([
            'telephone' => '772687847',
            'user_id' => \App\Models\User::factory()->create([
                'titulaire' => 'Moustapha Seck',
                'email' => 'seckmoustapha238@gmail.com',
            ])->id,
        ]);

        // Créer 74 autres clients sénégalais réalistes
        Client::factory()->count(74)->create();
    }
}
