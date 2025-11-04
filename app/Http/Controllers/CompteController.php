<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\User;
use App\Services\CompteService;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

     private CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompteRequest $request)
    {
        $this->authorize('create', Compte::class);

        $data = $request->validated();

        try {
            $result = $this->compteService->createCompteWithClient($data);

            return response()->json([
                'message' => 'Compte créé avec succès.',
                'compte' => $result['compte'],
                'client' => $result['client'],
                'user' => $result['user'],
                'generated_password' => $result['generated_password'],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur lors de la création du compte','error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
