<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\User;
use App\Services\CompteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    // public function store(StoreCompteRequest $request)
    // {
    //     // $this->authorize('create', Compte::class);
    //     try 
    //     {
    //         $data = $request->validated();
    //         $result = $this->compteService->createCompte($data);
    //         return response()->json([
    //             'message' => 'Compte créé avec succès.',
    //             'compte' => $result['compte'],
    //             'client' => $result['client'],
    //             'user' => $result['user'],
                
    //         ], 201);
    //     } catch (\Throwable $e) 
    //     {
    //         return response()->json(['message' => 'Erreur lors de la création du compte','error' => $e->getMessage()], 500);
    //     }
    // }

public function store(StoreCompteRequest $request)
{
    try 
    {
        Log::info('Début création compte', ['data' => $request->all()]);
        
        $data = $request->validated();
        Log::info('Données validées', ['validated_data' => $data]);
        
        $result = $this->compteService->createCompte($data);
        Log::info('Résultat création', ['result' => $result]);
        
        if (!$result) {
            throw new \Exception('Aucun résultat retourné par le service');
        }

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès.',
            'compte' => $result['compte'],
            'client' => $result['client'],
            'user' => $result['user'],
        ], 201, [], JSON_PRETTY_PRINT);
    } 
    catch (\Throwable $e) 
    {
        Log::error('Erreur création compte', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création du compte',
            'error' => $e->getMessage()
        ], 500, [], JSON_PRETTY_PRINT);
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
