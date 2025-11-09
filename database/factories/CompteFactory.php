<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         return [
            'id'=>$this->faker->uuid(),
            'num_compte' => $this->faker->unique()->numerify('C##########'), // 11 chiffres pour éviter les conflits
            'status'     => $this->faker->randomElement(['actif', 'actif', 'actif', 'bloque']), // Plus de comptes actifs
            'type'       => $this->faker->randomElement(['cheque','epargne']),
            'devise'     => $this->faker->randomElement(['XOF','XOF','XOF','EUR','USD']), // Plus de XOF
            'solde'      => $this->faker->numberBetween(5000, 50000), // Solde entre 5k et 50k
            'client_id'  => Client::factory(),
        ];
    }
}


