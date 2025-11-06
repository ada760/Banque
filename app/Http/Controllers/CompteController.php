<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\User;
use App\Services\CompteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompteController extends Controller
{


    private CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * Afficher la liste des comptes (avec filtres)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Compte::class);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
                'error' => 'Vous n\'avez pas les permissions nécessaires pour voir les comptes'
            ], 403);
        }

        // Récupère uniquement les filtres envoyés par l'utilisateur
        $filters = $request->only([
            'status',
            'type',
            'solde_min',
            'solde_max',
            'date_debut',
            'date_fin',
        ]);

        // Valeur par défaut si 'status' n'est pas défini
        $filters['status'] = $filters['status'] ?? 'actif';

        $user = auth()->user();

        // Si l'utilisateur est un client, ajouter le filtre client_id APRÈS avoir récupéré les autres filtres
        if ($user && $user->client) {
            $filters['client_id'] = $user->client->id;
        }

        // Pagination
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 10);

        // Délégation au service
        $comptes = $this->compteService->list($filters, $page, $limit);

        return response()->json([
            'success' => true,
            'data' => $comptes,
        ]);
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

    // public function store(StoreCompteRequest $request)
    // {
    //     try
    //     {

    //         $this->authorize('create', Compte::class);
    //         $data = $request->validated();

    //         $result = $this->compteService->createCompte($data);

    //         if (!$result) {
    //             throw new \Exception('Aucun résultat retourné par le service');
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Compte créé avec succès.',
    //             'compte' => $result['compte'],
    //             'client' => $result['client'],
    //             'user' => $result['user'],
    //         ], 201, [], JSON_PRETTY_PRINT);
    //     }
    //     catch (\Throwable $e)
    //     {

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors de la création du compte',
    //             'error' => $e->getMessage()
    //         ], 500, [], JSON_PRETTY_PRINT);
    //     }
    // }


    public function store(StoreCompteRequest $request)
    {
        try {
            // Gestion de l'autorisation avec try-catch
            try {
                $this->authorize('create', Compte::class);
            } catch (AuthorizationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé',
                    'error' => 'Vous n\'avez pas les permissions nécessaires'
                ], 403);
            }

            $data = $request->validated();
            $result = $this->compteService->createCompte($data);

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
        } catch (\Throwable $e) {
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
