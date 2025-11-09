<?php

namespace Database\Seeders;

use App\Models\Compte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un compte pour chaque client existant
        $clients = \App\Models\Client::all();

        foreach ($clients as $client) {
            Compte::factory()->createQuietly([
                'client_id' => $client->id,
            ]);
        }
    }
}
