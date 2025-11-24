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
        // Créer un client spécifique pour les tests - Moustapha
        $user = \App\Models\User::factory()->create([
            'titulaire' => 'Moustapha Seck',
            'email' => 'seckmoustapha238@gmail.com',
            'phone_number' => '772687847',
            'secret_code' => bcrypt('1234'), // Code secret pour les tests
        ]);

        Client::factory()->create([
            'telephone' => '772687847',
            'user_id' => $user->id,
        ]);

        // Créer 74 autres clients sénégalais réalistes
        Client::factory()->count(74)->create();
    }
}
