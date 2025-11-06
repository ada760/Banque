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
            'num_compte' => $this->faker->unique()->numerify('C00######'),
            'status'     => $this->faker->randomElement(['bloque','actif', 'ferme', 'suspendu']),
            'type'       => $this->faker->randomElement(['cheque','epargne']),
            'devise'     => $this->faker->randomElement(['XOF','EUR','USD']),
            'client_id'  => Client::factory(), 
        ];
    }
}


