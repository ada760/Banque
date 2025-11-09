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
     * Prénoms sénégalais courants
     */
    private array $prenomsHomme = [
        'Moussa', 'Moustapha', 'Babacar', 'Abdoulaye', 'Ibrahima', 'Cheikh',
        'Omar', 'Amadou', 'Saliou', 'Modou', 'Pape', 'Serigne', 'El Hadji',
        'Mamadou', 'Alioune', 'Samba', 'Lamine', 'Souleymane', 'Malick', 'Thierno'
    ];

    private array $prenomsFemme = [
        'Fatou', 'Aïssatou', 'Khady', 'Mariama', 'Aminata', 'Adama',
        'Ndeye', 'Seynabou', 'Mame', 'Astou', 'Penda', 'Dior', 'Sokhna',
        'Maimouna', 'Hawa', 'Rokhaya', 'Yacine', 'Awa', 'Oumou', 'Khadija'
    ];

    /**
     * Noms de famille sénégalais courants
     */
    private array $nomsFamille = [
        'Ndiaye', 'Diop', 'Sarr', 'Fall', 'Seck', 'Gueye', 'Ba', 'Sow',
        'Kane', 'Sy', 'Mbaye', 'Thiam', 'Dia', 'Wade', 'Diagne', 'Faye',
        'Sall', 'Niang', 'Cisse', 'Traore', 'Diallo', 'Camara', 'Barry', 'Keita'
    ];

    /**
     * Villes sénégalaises
     */
    private array $villes = [
        'Dakar', 'Thiès', 'Saint-Louis', 'Kaolack', 'Mbour', 'Ziguinchor',
        'Diourbel', 'Louga', 'Tambacounda', 'Kolda', 'Matam', 'Kaffrine',
        'Fatick', 'Sédhiou', 'Kédougou', 'Keur Massar', 'Guédiawaye',
        'Rufisque', 'Bargny', 'Joal-Fadiouth'
    ];

    /**
     * Génère un numéro de téléphone Orange Money Sénégalais
     * Format: 77XXXXXXX ou 78XXXXXXX (9 chiffres total)
     */
    private function generateOrangeMoneyNumber(): string
    {
        $prefix = $this->faker->randomElement(['77', '78']);
        $number = $this->faker->numerify('#######'); // 7 chiffres
        return $prefix . $number;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Choisir un genre aléatoirement
        $isHomme = $this->faker->boolean();

        // Sélectionner prénom et nom appropriés
        $prenom = $isHomme
            ? $this->faker->randomElement($this->prenomsHomme)
            : $this->faker->randomElement($this->prenomsFemme);

        $nom = $this->faker->randomElement($this->nomsFamille);
        $ville = $this->faker->randomElement($this->villes);

        // Générer un numéro de téléphone unique
        $telephone = $this->generateOrangeMoneyNumber();

        // Créer une adresse sénégalaise réaliste
        $quartiers = ['Plateau', 'Mermoz', 'Ouakam', 'Parcelles Assainies', 'Keur Massar', 'Pikine', 'Guédiawaye'];
        $quartier = $this->faker->randomElement($quartiers);
        $adresse = $this->faker->streetAddress() . ', ' . $quartier . ', ' . $ville;

        // Générer un email unique basé sur le nom avec un suffixe aléatoire
        $emailBase = strtolower($prenom . '.' . $nom);
        $suffix = $this->faker->randomNumber(3, true); // Nombre aléatoire de 3 chiffres
        $email = $emailBase . $suffix . '@' . $this->faker->randomElement(['gmail.com', 'yahoo.fr', 'hotmail.com', 'outlook.com']);

        return [
            'id' => $this->faker->uuid(),
            'telephone' => $telephone,
            'cni' => $this->faker->unique()->numerify('############'), // 12 chiffres
            'adresse' => $adresse,
            'user_id' => User::factory()->state([
                'titulaire' => $prenom . ' ' . $nom,
                'email' => $email
            ]),
        ];
    }
}
