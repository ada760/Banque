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
        // Note: L'utilisateur de test Moustapha est maintenant créé via migration
        // pour éviter les conflits en production

        // Créer 75 clients sénégalais réalistes (au lieu de 74)
        Client::factory()->count(75)->create();
    }
}
