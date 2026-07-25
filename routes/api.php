<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BoutiqueController;
use App\Http\Controllers\Api\V1\CommandeController;
use App\Http\Controllers\Api\V1\FcmTokenController;
use App\Http\Controllers\Api\V1\GeoController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PmeController;
use App\Http\Controllers\Api\V1\ProduitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:public')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
        Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:auth');
        Route::post('/auth/resend-email-code', [AuthController::class, 'resendEmailCode'])->middleware('throttle:auth');
        Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

        Route::middleware(['auth:sanctum', 'throttle:auth'])->group(function () {
            Route::post('/auth/logout', [AuthController::class, 'logout']);
            Route::get('/auth/me', [AuthController::class, 'me']);
            Route::put('/auth/profil', [AuthController::class, 'updateProfil']);
            Route::put('/auth/password', [AuthController::class, 'changePassword']);

            Route::post('/fcm/token', [FcmTokenController::class, 'store']);

            Route::post('/commandes', [CommandeController::class, 'store']);
            Route::get('/commandes', [CommandeController::class, 'index']);
            Route::get('/commandes/{id}', [CommandeController::class, 'show']);

            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::put('/notifications/{id}/lu', [NotificationController::class, 'markRead']);
            Route::put('/notifications/tout-lire', [NotificationController::class, 'markAllRead']);
            Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        });

        Route::middleware(['auth.optional'])->group(function () {
            Route::get('/boutiques', [BoutiqueController::class, 'index']);
            Route::get('/boutiques/{id}', [BoutiqueController::class, 'show']);

            Route::get('/produits', [ProduitController::class, 'index']);
            Route::get('/produits/categories', [ProduitController::class, 'categories']);
            Route::get('/produits/{id}', [ProduitController::class, 'show']);
        });

        Route::get('/wilayas', [GeoController::class, 'wilayas']);
        Route::get('/communes/{wilaya}', [GeoController::class, 'communes']);
    });

    Route::prefix('pme')->middleware(['auth.pme', 'throttle:public'])->group(function () {
        Route::post('/sync-clients', [PmeController::class, 'syncClients']);
        Route::post('/sync-produits', [PmeController::class, 'syncProduits']);
        Route::post('/sync-fournisseur', [PmeController::class, 'syncFournisseur']);
        Route::get('/commandes', [PmeController::class, 'commandes']);
        Route::get('/commandes/export-csv', [PmeController::class, 'exportCommandesCsv']);
        Route::put('/commandes/{id}/status', [PmeController::class, 'updateCommandeStatus']);
        Route::put('/commandes/{id}/sync', [PmeController::class, 'markSynced']);
    });
});
