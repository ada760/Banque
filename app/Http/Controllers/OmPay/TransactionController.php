<?php

namespace App\Http\Controllers\OmPay;

use App\Http\Controllers\Controller;
use App\Services\OmPay\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Transactions OM Pay",
 *     description="Gestion des transactions OM Pay"
 * )
 */

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Get(
     *     path="/ompay/transactions",
     *     summary="Récupérer les transactions de l'utilisateur avec filtrage et pagination",
     *     description="Retourne la liste filtrée et paginée des transactions OM Pay de l'utilisateur authentifié",
     *     tags={"Transactions OM Pay"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"transfer", "payment"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "success", "failed"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="min_amount",
     *         in="query",
     *         description="Montant minimum",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0)
     *     ),
     *     @OA\Parameter(
     *         name="max_amount",
     *         in="query",
     *         description="Montant maximum",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche dans la description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="user_id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                 @OA\Property(property="recipient_phone", type="string", nullable=true, example=null),
     *                 @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment"),
     *                 @OA\Property(property="amount", type="number", format="float", example=500.00),
     *                 @OA\Property(property="status", type="string", enum={"pending", "success", "failed"}, example="success"),
     *                 @OA\Property(property="reference", type="string", nullable=true, example=null),
     *                 @OA\Property(property="merchant_id", type="string", example="merchant_123"),
     *                 @OA\Property(property="service_id", type="string", example="service_456"),
     *                 @OA\Property(property="fee", type="number", format="float", example=5.00),
     *                 @OA\Property(property="description", type="string", example="Paiement de facture"),
     *                 @OA\Property(property="transaction_date", type="string", format="date-time", example="2025-11-10T15:35:00.373000Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="id", type="string", example="69120624c02cc5e99b075eb2")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=150),
     *                 @OA\Property(property="last_page", type="integer", example=15),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=10)
     *             ),
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="applied", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="type", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string", nullable=true),
     *                 @OA\Property(property="date_from", type="string", nullable=true),
     *                 @OA\Property(property="date_to", type="string", nullable=true),
     *                 @OA\Property(property="min_amount", type="number", nullable=true),
     *                 @OA\Property(property="max_amount", type="number", nullable=true),
     *                 @OA\Property(property="search", type="string", nullable=true)
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
        $userId = auth()->id();

        // Paramètres de pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        // Paramètres de filtrage
        $filters = [
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'min_amount' => $request->get('min_amount'),
            'max_amount' => $request->get('max_amount'),
            'search' => $request->get('search'),
        ];

        $result = $this->transactionService->getFilteredTransactions($userId, $filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
            'filters' => $result['filters'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/ompay/transactions",
     *     summary="Créer une nouvelle transaction OM Pay",
     *     description="Crée une nouvelle transaction OM Pay (transfert ou paiement)",
     *     tags={"Transactions OM Pay"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","amount"},
     *             @OA\Property(property="recipient_phone", type="string", nullable=true, example=null, description="Numéro du destinataire pour les transferts"),
     *             @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment", description="Type de transaction"),
     *             @OA\Property(property="amount", type="number", format="float", minimum=100, example=500.00, description="Montant de la transaction (minimum 100)"),
     *             @OA\Property(property="reference", type="string", nullable=true, example=null, description="Référence de la transaction"),
     *             @OA\Property(property="merchant_id", type="string", nullable=true, example="merchant_123", description="ID du marchand"),
     *             @OA\Property(property="service_id", type="string", nullable=true, example="service_456", description="ID du service"),
     *             @OA\Property(property="qr_metadata", type="object", nullable=true, description="Métadonnées QR code"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Paiement de facture", description="Description de la transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction OM Pay créée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                 @OA\Property(property="recipient_phone", type="string", nullable=true, example=null),
     *                 @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment"),
     *                 @OA\Property(property="amount", type="number", format="float", example=500.00),
     *                 @OA\Property(property="status", type="string", enum={"pending", "success", "failed"}, example="success"),
     *                 @OA\Property(property="reference", type="string", nullable=true, example=null),
     *                 @OA\Property(property="merchant_id", type="string", example="merchant_123"),
     *                 @OA\Property(property="service_id", type="string", example="service_456"),
     *                 @OA\Property(property="fee", type="number", format="float", example=5.00),
     *                 @OA\Property(property="description", type="string", example="Paiement de facture"),
     *                 @OA\Property(property="transaction_date", type="string", format="date-time", example="2025-11-10T15:35:00.373000Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="id", type="string", example="69120624c02cc5e99b075eb2")
     *             )
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
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création de la transaction OM Pay"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Vérifier les permissions - Temporarily commented for testing
        // $this->authorize('createTransaction', \App\Models\OmPay\Transaction::class);

        $validated = $request->validate([
            'recipient_phone' => 'nullable|string',
            'type' => 'required|in:transfer,payment',
            'amount' => 'required|numeric|min:100',
            'reference' => 'nullable|string',
            'merchant_id' => 'nullable|string',
            'service_id' => 'nullable|string',
            'qr_metadata' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        // Use authenticated user ID
        $validated['user_id'] = auth()->id();

        try {
            $transaction = $this->transactionService->createTransaction($validated);

            return response()->json([
                'success' => true,
                'message' => 'Transaction OM Pay créée avec succès',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la transaction OM Pay',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ompay/transactions/stats",
     *     summary="Récupérer les statistiques des transactions",
     *     description="Retourne les statistiques des transactions OM Pay de l'utilisateur",
     *     tags={"Transactions OM Pay"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_transactions", type="integer", example=25),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=12500.00),
     *                 @OA\Property(property="successful_transactions", type="integer", example=23),
     *                 @OA\Property(property="failed_transactions", type="integer", example=2),
     *                 @OA\Property(property="pending_transactions", type="integer", example=0),
     *                 @OA\Property(property="average_transaction", type="number", format="float", example=500.00),
     *                 @OA\Property(property="monthly_stats", type="object",
     *                     @OA\Property(property="current_month", type="object",
     *                         @OA\Property(property="count", type="integer", example=5),
     *                         @OA\Property(property="amount", type="number", format="float", example=2500.00)
     *                     )
     *                 )
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
    public function stats(): JsonResponse
    {
        $userId = auth()->id();
        $stats = $this->transactionService->getTransactionStats($userId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/ompay/comptes/{id}/transactions",
     *     summary="Récupérer les transactions OM Pay d'un compte bancaire",
     *     description="Retourne les transactions OM Pay de l'utilisateur propriétaire du compte bancaire spécifié",
     *     tags={"Transactions OM Pay"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du compte bancaire",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="user_id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                 @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment"),
     *                 @OA\Property(property="amount", type="number", format="float", example=500.00),
     *                 @OA\Property(property="status", type="string", enum={"pending", "success", "failed"}, example="success"),
     *                 @OA\Property(property="description", type="string", example="Paiement de facture"),
     *                 @OA\Property(property="transaction_date", type="string", format="date-time", example="2025-11-10T15:35:00.373000Z")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="last_page", type="integer", example=3)
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
     *         description="Accès non autorisé - Le compte n'appartient pas à l'utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé à ce compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     )
     * )
     */
    public function getTransactionsByCompte(Request $request, string $compteId): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Vérifier que le compte existe et appartient à l'utilisateur
        $compte = \App\Models\Compte::find($compteId);

        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé'
            ], 404);
        }

        // Vérifier que le compte appartient au client de l'utilisateur
        if ($compte->client_id !== $user->client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce compte'
            ], 403);
        }

        // Récupérer les transactions de l'utilisateur (propriétaire du compte)
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $result = $this->transactionService->getFilteredTransactions($user->id, [], $perPage, $page);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/ompay/mon-compte",
     *     summary="Voir les informations du compte OM Pay",
     *     description="Retourne les informations personnelles et le QR code de l'utilisateur connecté",
     *     tags={"Transactions OM Pay"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations du compte récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Informations du compte récupérées avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="a05818f4-df52-3d88-b897-9a5e6019afc1"),
     *                 @OA\Property(property="prenom", type="string", example="Moustapha"),
     *                 @OA\Property(property="nom", type="string", example="Seck"),
     *                 @OA\Property(property="telephone", type="string", example="772687847"),
     *                 @OA\Property(property="solde", type="number", format="float", example=35420.50),
     *                 @OA\Property(property="qr_code_url", type="string", example="https://banque-api.com/storage/qrcodes/772687847.png")
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
    public function monCompte(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Récupérer le client associé à l'utilisateur
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé'
            ], 404);
        }

        // Récupérer le compte actif
        $compteActif = $client->compte->where('status', 'actif')->first();

        if (!$compteActif) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun compte actif trouvé'
            ], 404);
        }

        // Générer le contenu du QR code
        $qrContent = "ompay://paiement?numero=" . $client->telephone;

        // Pour l'instant, utiliser un service externe QR code (remplacer par génération locale quand Imagick sera installé)
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qrContent);

        return response()->json([
            'success' => true,
            'message' => 'Informations du compte récupérées avec succès',
            'data' => [
                'id' => $client->id,
                'prenom' => $user->titulaire ? explode(' ', $user->titulaire)[0] : '',
                'nom' => $user->titulaire ? explode(' ', $user->titulaire)[1] ?? '' : '',
                'telephone' => $client->telephone,
                'solde' => $compteActif->solde,
                'qr_code_url' => $qrCodeUrl
            ]
        ]);
    }
}