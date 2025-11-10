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
     *     summary="Récupérer les transactions de l'utilisateur",
     *     description="Retourne la liste des transactions OM Pay de l'utilisateur authentifié",
     *     tags={"Transactions OM Pay"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre maximum de transactions à retourner",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
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
     *             ))
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
        $limit = $request->get('limit', 10);

        $transactions = $this->transactionService->getUserTransactions($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $transactions,
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
        // Vérifier les permissions
        $this->authorize('createTransaction', \App\Models\OmPay\Transaction::class);

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
}