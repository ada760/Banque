<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// Authentication routes
Route::post('/login', [AuthController::class, 'login']);

// OM Pay Authentication routes (sans middleware auth)
Route::prefix('auth')->group(function () {
    Route::post('/request-otp', [\App\Http\Controllers\OmAuthController::class, 'requestOtp'])->middleware('throttle:3,1'); // 3 par minute
    Route::post('/verify-otp', [\App\Http\Controllers\OmAuthController::class, 'verifyOtp']);
    Route::post('/set-secret-code', [\App\Http\Controllers\OmAuthController::class, 'setSecretCode']);
    Route::post('/login', [\App\Http\Controllers\OmAuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\OmAuthController::class, 'logout'])->middleware('auth:api');
    Route::get('/check', [\App\Http\Controllers\OmAuthController::class, 'checkAuth'])->middleware('auth:api');
});


// Account routes (protected by authentication and role middleware)
// Route::middleware(['auth:api'])->group(function () {
    // Route::apiResource('comptes', CompteController::class);
// });
Route::middleware(['auth:api'])->group(function () {
    // Routes comptes bancaires (PostgreSQL) - Admin seulement
    Route::middleware(['can:create,App\Models\Compte'])->group(function () {
        Route::post('/comptes', [ CompteController::class ,'store' ]);
    });
    Route::middleware(['can:viewAny,App\Models\Compte'])->group(function () {
        Route::get('/comptes', [CompteController::class, 'index']);
    });
});

// Routes OM Pay (MongoDB) - Protected by authentication
Route::middleware(['auth:api'])->prefix('ompay')->group(function () {
    Route::get('/transactions', [\App\Http\Controllers\OmPay\TransactionController::class, 'index']);
    Route::get('/transactions/stats', [\App\Http\Controllers\OmPay\TransactionController::class, 'stats']);
    Route::post('/transactions', [\App\Http\Controllers\OmPay\TransactionController::class, 'store']);
    Route::get('/mon-compte', [\App\Http\Controllers\OmPay\TransactionController::class, 'monCompte']);
    Route::get('/comptes/{id}/transactions', [\App\Http\Controllers\OmPay\TransactionController::class, 'getTransactionsByCompte']);
});

// User profile endpoint
Route::middleware(['auth:api'])->get('/me', [\App\Http\Controllers\OmAuthController::class, 'me']);

// Routes clients - Protected by authentication
Route::middleware(['auth:api'])->prefix('clients')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\ClientController::class, 'dashboard']);
});


// Health check API route
Route::get('/health', function () {
    try {
        // Vérifier PostgreSQL
        DB::connection('pgsql')->getPdo();
        $db_status = '✅ PostgreSQL OK';

        return response()->json([
            'success' => true,
            'message' => 'API Health Check',
            'status' => 'healthy',
            'timestamp' => now(),
            'services' => [
                'database' => $db_status,
            ],
            'version' => config('app.version', '1.0.0'),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'API Health Check Failed',
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => now(),
        ], 500);
    }
});

// Debug route to check database contents (temporary)
Route::get('/debug/data', function () {
    try {
        $users = \App\Models\User::select('id', 'titulaire', 'phone_number', 'email')->get();
        $clients = \App\Models\Client::select('id', 'telephone', 'user_id')->get();
        $comptes = \App\Models\Compte::select('id', 'num_compte', 'solde', 'client_id', 'status')->get();

        return response()->json([
            'success' => true,
            'message' => 'Database contents',
            'data' => [
                'users' => $users,
                'clients' => $clients,
                'comptes' => $comptes
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Swagger Documentation routes
Route::get('/documentation', [App\Http\Controllers\SwaggerController::class, 'index']);
Route::get('/documentation.json', [App\Http\Controllers\SwaggerController::class, 'json']);
