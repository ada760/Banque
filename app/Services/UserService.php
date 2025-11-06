<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Retourne [User $user, ?string $generatedPassword]
     * Crée un user si l'email n'existe pas.
     */
    // public function findOrCreate(array $data): array
    // {
    //     $user = User::where('email', $data['email'])->first();
    //     $generatedPassword = null;

    //     if (! $user) {
    //         $generatedPassword = Str::random(10);
    //         $user = User::create([
    //             'name' => $data['name'] ?? explode('@', $data['email'])[0],
    //             'email' => $data['email'],
    //             'password' => bcrypt($generatedPassword),
    //         ]);
    //     }

    //     return [$user, $generatedPassword];
    // }


    public function create(array $data): array
    {

        $generatedPassword = Str::random(10);

        $user = User::create([
            'titulaire' => $data['titulaire'],
            'email' => $data['email'],
            'password' => Hash::make($generatedPassword),
        ]);

        return [$user, $generatedPassword];
    }

    /**
     * Trouve un utilisateur par email
     */
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
