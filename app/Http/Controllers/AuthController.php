<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService; 
    }


    public function login(LoginRequest $request)
    {
        try 
        {
            $playload = $this->authService->loginService($request);
            return response()->json($playload);
        } catch (\Error $error) {
            return response()->json($error);
        }
        
    }
   

    /**
     * Logout user (revoke token).
     */
    // public function logout(Request $request)
    // {
    //     $request->user()->token()->revoke();

    //     return response()->json(['message' => 'Successfully logged out']);
    // }

    /**
     * Get authenticated user.
     */
    // public function user(Request $request)
    // {
    //     return response()->json($request->user());
    // }
}
