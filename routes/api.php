<?php

use Illuminate\Support\Facades\Route;
use Presentation\UserApiController;
use Presentation\CompteBancaireController;
use Presentation\UserAccountController;
use Presentation\ApiGatewayController;

// Microservice Utilisateur
Route::apiResource('users', UserApiController::class);

// Microservice CompteBancaire
Route::apiResource('accounts', CompteBancaireController::class);

// Routes spécifiques pour les comptes bancaires
Route::get('accounts/user/{userId}', [CompteBancaireController::class, 'getByUser']);
Route::delete('accounts/user/{userId}', [CompteBancaireController::class, 'deleteByUser']);

// Orchestration atomique User + CompteBancaire
Route::post('user-accounts', [UserAccountController::class, 'store']);
Route::delete('user-accounts/{userId}', [UserAccountController::class, 'destroy']);

// API Gateway - Requêtes transverses
Route::prefix('gateway')->group(function () {
    Route::get('users/{userId}/complete', [ApiGatewayController::class, 'getUserComplete']);
    Route::get('users/complete', [ApiGatewayController::class, 'getAllUsersComplete']);
    Route::get('stats', [ApiGatewayController::class, 'getStats']);
    Route::get('health', [ApiGatewayController::class, 'health']);
});
