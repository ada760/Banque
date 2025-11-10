<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\User;
use App\Services\CompteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 */

class CompteController extends Controller
{


    private CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * @OA\Get(
     *     path="/comptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste des comptes bancaires avec possibilité de filtrage",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut (actif, bloque, ferme, suspendu)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme", "suspendu"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type (epargne, cheque)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="solde_min",
     *         in="query",
     *         description="Solde minimum",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="solde_max",
     *         in="query",
     *         description="Solde maximum",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="62028976-381e-3a70-9073-df6de0fd5e74"),
     *                     @OA\Property(property="num_compte", type="string", example="C0081999796"),
     *                     @OA\Property(property="status", type="string", enum={"actif", "bloque", "ferme", "suspendu"}, example="actif"),
     *                     @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque"),
     *                     @OA\Property(property="devise", type="string", example="XOF"),
     *                     @OA\Property(property="solde", type="number", format="float", example=8728.00),
     *                     @OA\Property(property="client", type="object",
     *                         @OA\Property(property="id", type="string", example="10281d44-9f2d-3008-8c61-7d55a71b538f"),
     *                         @OA\Property(property="telephone", type="string", example="772687847"),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                             @OA\Property(property="titulaire", type="string", example="Abdoulaye Seck"),
     *                             @OA\Property(property="email", type="string", example="seckmoustapha238@gmail.com")
     *                         )
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string"),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="prev_page_url", type="string"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     )
     * )
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


    /**
     * @OA\Post(
     *     path="/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte bancaire",
     *     description="Crée un nouveau compte bancaire avec un client associé",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","devise","status"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque", description="Type de compte"),
     *             @OA\Property(property="devise", type="string", example="XOF", description="Devise du compte"),
     *             @OA\Property(property="status", type="string", enum={"bloque", "actif", "ferme", "suspendu"}, example="actif", description="Statut du compte"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="client@example.com", description="Email du client"),
     *             @OA\Property(property="telephone", type="string", nullable=true, example="772687847", description="Téléphone du client (9 chiffres)"),
     *             @OA\Property(property="adresse", type="string", nullable=true, example="Dakar, Sénégal", description="Adresse du client"),
     *             @OA\Property(property="cni", type="string", nullable=true, example="1234567890123", description="Numéro CNI (13-14 chiffres)"),
     *             @OA\Property(property="titulaire", type="string", nullable=true, example="Moustapha Seck", description="Nom du titulaire")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès."),
     *             @OA\Property(property="compte", type="object",
     *                 @OA\Property(property="id", type="string", example="62028976-381e-3a70-9073-df6de0fd5e74"),
     *                 @OA\Property(property="num_compte", type="string", example="C0081999796"),
     *                 @OA\Property(property="status", type="string", example="actif"),
     *                 @OA\Property(property="type", type="string", example="cheque"),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="solde", type="number", format="float", example=0.00),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="string", example="10281d44-9f2d-3008-8c61-7d55a71b538f"),
     *                 @OA\Property(property="telephone", type="string", example="772687847"),
     *                 @OA\Property(property="cni", type="string", example="1234567890123"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                 @OA\Property(property="titulaire", type="string", example="Moustapha Seck"),
     *                 @OA\Property(property="email", type="string", example="client@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="generatedPassword", type="string", nullable=true, example="AbCdEf123", description="Mot de passe généré pour le nouveau client"),
     *             @OA\Property(property="isNewClient", type="boolean", example=true, description="Indique si c'est un nouveau client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création du compte"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
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
                'generatedPassword' => $result['generatedPassword'] ?? null,
                'isNewClient' => $result['isNewClient'] ?? true,
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
