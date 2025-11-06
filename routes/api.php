<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use Illuminate\Http\Request;
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


// Account routes (protected by authentication and role middleware)
// Route::middleware(['auth:api'])->group(function () {
    // Route::apiResource('comptes', CompteController::class);
// });
Route::middleware(['auth:api'])->group(function () {
Route::post('/comptes', [ CompteController::class ,'store' ]);
Route::get('/comptes', [CompteController::class, 'index']);

});
