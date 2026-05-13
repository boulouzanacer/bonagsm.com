<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\FrsAuthController;
use App\Http\Controllers\Fournisseur\DashboardController as FrsDashboardController;
use App\Http\Controllers\Fournisseur\ProduitController as FrsProduitController;
use App\Http\Controllers\Fournisseur\CategorieController as FrsCategorieController;
use App\Http\Controllers\Fournisseur\ClientController as FrsClientController;
use App\Http\Controllers\Fournisseur\CommandeController as FrsCommandeController;
use App\Http\Controllers\Fournisseur\ProfileController as FrsProfileController;
use App\Http\Controllers\StoreController;

Route::get('/', [StoreController::class, 'index']);
Route::get('/boutiques/{id}', [StoreController::class, 'boutique']);
Route::get('/produits/{id}', [StoreController::class, 'produit']);

Route::get('/login', [ClientAuthController::class, 'showLogin']);
Route::post('/login', [ClientAuthController::class, 'login']);
Route::get('/register', [ClientAuthController::class, 'showRegister']);
Route::post('/register', [ClientAuthController::class, 'register']);
Route::post('/register/verify-email', [ClientAuthController::class, 'verifyEmail']);
Route::post('/register/resend-email-code', [ClientAuthController::class, 'resendEmailCode']);
Route::post('/logout', [ClientAuthController::class, 'logout']);

Route::get('/panier', [StoreController::class, 'panier']);
Route::post('/panier/add', [StoreController::class, 'panierAdd']);
Route::post('/panier/update', [StoreController::class, 'panierUpdate']);
Route::post('/panier/remove', [StoreController::class, 'panierRemove']);
Route::post('/panier/clear', [StoreController::class, 'panierClear']);

Route::get('/checkout', [StoreController::class, 'checkout']);
Route::post('/checkout', [StoreController::class, 'checkoutStore']);

Route::get('/mes-commandes', [StoreController::class, 'mesCommandes']);
Route::get('/mes-commandes/{id}', [StoreController::class, 'commandeShow']);

Route::get('/fournisseur/login', function () {
    return view('auth.fournisseur-login');
});

Route::post('/fournisseur/login', [FrsAuthController::class, 'login']);
Route::post('/fournisseur/logout', [FrsAuthController::class, 'logout']);

Route::prefix('fournisseur')->middleware('auth.fournisseur')->group(function () {
    Route::get('/dashboard', [FrsDashboardController::class, 'index']);

    Route::get('/categories', [FrsCategorieController::class, 'index']);
    Route::get('/categories/create', [FrsCategorieController::class, 'create']);
    Route::post('/categories', [FrsCategorieController::class, 'store']);
    Route::get('/categories/{id}/edit', [FrsCategorieController::class, 'edit']);
    Route::put('/categories/{id}', [FrsCategorieController::class, 'update']);
    Route::delete('/categories/{id}', [FrsCategorieController::class, 'destroy']);

    Route::get('/produits', [FrsProduitController::class, 'index']);
    Route::get('/produits/create', [FrsProduitController::class, 'create']);
    Route::post('/produits', [FrsProduitController::class, 'store']);
    Route::get('/produits/{id}', [FrsProduitController::class, 'show']);
    Route::get('/produits/{id}/edit', [FrsProduitController::class, 'edit']);
    Route::put('/produits/{id}', [FrsProduitController::class, 'update']);
    Route::delete('/produits/{id}', [FrsProduitController::class, 'destroy']);
    Route::post('/produits/{id}/toggle-actif', [FrsProduitController::class, 'toggleActif']);

    Route::get('/clients', [FrsClientController::class, 'index']);
    Route::get('/clients/{id}', [FrsClientController::class, 'show']);

    Route::get('/commandes', [FrsCommandeController::class, 'index']);
    Route::get('/commandes/{id}', [FrsCommandeController::class, 'show']);
    Route::put('/commandes/{id}/statut', [FrsCommandeController::class, 'updateStatut']);

    Route::get('/profil', [FrsProfileController::class, 'edit']);
    Route::put('/profil', [FrsProfileController::class, 'update']);
    Route::put('/profil/password', [FrsProfileController::class, 'updatePassword']);

    Route::get('/wilayas/{idWilaya}/communes', [FrsProfileController::class, 'communes']);
});
