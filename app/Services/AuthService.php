<?php

namespace App\Services;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    
   public function loginService(LoginRequest $request)
   {
        $validatedData = $request->validated();
        
        $user = User::where('email', $validatedData["email"])->first();
     
       
        if(!$user || !Hash::check($validatedData['password'],$user->password))
        {  
             throw new \Exception('Email ou mot de passe incorrect', 401);
        }
       
        $token = $user->createToken('access token')->accessToken;

         return [
            'data' => [
                'id' => $user->id,
                'titulaire' => $user->titulaire,
                'email' => $user->email,
                'role' => $user->role, 
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ];
   
   }

}
