<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
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
            'telephone' => $this->faker->unique()->phoneNumber(),
            'cni'       => $this->faker->unique()->numerify('############'), // 12 chiffres
            'adresse'   => $this->faker->address(),
            'user_id'   => User::factory(),
        ];
    }
}
