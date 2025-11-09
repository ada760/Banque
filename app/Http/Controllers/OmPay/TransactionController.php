<?php

namespace App\Http\Controllers\OmPay;

use App\Http\Controllers\Controller;
use App\Services\OmPay\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

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

    public function store(Request $request): JsonResponse
    {
        // Vérifier les permissions
        $this->authorize('createTransaction', \App\Models\OmPay\Transaction::class);

        $validated = $request->validate([
            'recipient_phone' => 'nullable|string',
            'type' => 'required|in:transfer,payment,recharge',
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