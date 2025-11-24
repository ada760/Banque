<?php

namespace App\Http\Controllers;

use App\Services\OmPay\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients OM Pay"
 * )
 */
class ClientController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Get(
     *     path="/clients/dashboard",
     *     summary="Tableau de bord du client",
     *     description="Retourne les informations du client connecté, ses comptes actifs et l'historique des transactions",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations du dashboard récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                     @OA\Property(property="titulaire", type="string", example="Moustapha Seck"),
     *                     @OA\Property(property="email", type="string", example="seckmoustapha238@gmail.com")
     *                 ),
     *                 @OA\Property(property="compte_actif", type="object",
     *                     @OA\Property(property="id", type="string", example="62028976-381e-3a70-9073-df6de0fd5e74"),
     *                     @OA\Property(property="num_compte", type="string", example="C2472371825"),
     *                     @OA\Property(property="solde", type="number", format="float", example=37440.50),
     *                     @OA\Property(property="devise", type="string", example="USD")
     *                 ),
     *                 @OA\Property(property="historique_transactions", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="69120624c02cc5e99b075eb2"),
     *                     @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment"),
     *                     @OA\Property(property="amount", type="number", format="float", example=500.00),
     *                     @OA\Property(property="status", type="string", enum={"pending", "success", "failed"}, example="success"),
     *                     @OA\Property(property="description", type="string", example="Paiement de facture"),
     *                     @OA\Property(property="transaction_date", type="string", format="date-time", example="2025-11-10T15:35:00.373000Z")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client ou compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Client non trouvé")
     *         )
     *     )
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

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

        // Récupérer l'historique des transactions (dernières 10 transactions)
        $transactions = $this->transactionService->getFilteredTransactions(
            $user->id,
            [],
            10,
            1
        )['data'];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'titulaire' => $user->titulaire,
                    'email' => $user->email
                ],
                'compte_actif' => [
                    'id' => $compteActif->id,
                    'num_compte' => $compteActif->num_compte,
                    'solde' => $compteActif->solde,
                    'devise' => $compteActif->devise
                ],
                'historique_transactions' => $transactions
            ]
        ]);
    }
}