<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\FrsAuthController;
use App\Http\Controllers\Fournisseur\DashboardController as FrsDashboardController;
use App\Http\Controllers\Fournisseur\ProduitController as FrsProduitController;
use App\Http\Controllers\Fournisseur\CategorieController as FrsCategorieController;
use App\Http\Controllers\Fournisseur\SousCategorieController as FrsSousCategorieController;
use App\Http\Controllers\Fournisseur\MarqueController as FrsMarqueController;
use App\Http\Controllers\Fournisseur\ClientController as FrsClientController;
use App\Http\Controllers\Fournisseur\CommandeController as FrsCommandeController;
use App\Http\Controllers\Fournisseur\FraisLivraisonController as FrsFraisLivraisonController;
use App\Http\Controllers\Fournisseur\ProfileController as FrsProfileController;
use App\Http\Controllers\Fournisseur\SiteSettingsController as FrsSiteSettingsController;
use App\Http\Controllers\Fournisseur\UtilisateurController as FrsUtilisateurController;
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

Route::get('/wishlist', [StoreController::class, 'wishlist']);
Route::post('/wishlist/add', [StoreController::class, 'wishlistAdd']);
Route::post('/wishlist/remove', [StoreController::class, 'wishlistRemove']);

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

    Route::resource('/categories', FrsCategorieController::class)->except(['show']);
    Route::resource('/sous-categories', FrsSousCategorieController::class)->except(['show']);
    Route::resource('/marques', FrsMarqueController::class)->except(['show']);

    Route::get('/produits', [FrsProduitController::class, 'index']);
    Route::get('/produits/create', [FrsProduitController::class, 'create']);
    Route::post('/produits', [FrsProduitController::class, 'store']);
    Route::get('/produits/{id}', [FrsProduitController::class, 'show'])->whereNumber('id');
    Route::get('/produits/{id}/edit', [FrsProduitController::class, 'edit'])->whereNumber('id');
    Route::put('/produits/{id}', [FrsProduitController::class, 'update'])->whereNumber('id');

    Route::get('/clients', [FrsClientController::class, 'index']);
    Route::get('/clients/{id}', [FrsClientController::class, 'show'])->whereNumber('id');
    Route::put('/clients/{id}/tarif', [FrsClientController::class, 'updateTarif'])->whereNumber('id');

    Route::get('/commandes', [FrsCommandeController::class, 'index']);
    Route::get('/commandes/{id}', [FrsCommandeController::class, 'show'])->whereNumber('id');
    Route::put('/commandes/{id}/statut', [FrsCommandeController::class, 'updateStatut'])->whereNumber('id');
    Route::put('/commandes/{id}/lignes/{ligneId}', [FrsCommandeController::class, 'updateLigneQuantite'])
        ->whereNumber('id')
        ->whereNumber('ligneId');

    Route::get('/frais-livraison', [FrsFraisLivraisonController::class, 'index']);

    Route::get('/wilayas/{idWilaya}/communes', [FrsProfileController::class, 'communes'])->whereNumber('idWilaya');

    Route::middleware('auth.admin')->group(function () {
        Route::post('/produits/import', [FrsProduitController::class, 'import']);
        Route::delete('/produits/{id}', [FrsProduitController::class, 'destroy'])->whereNumber('id');
        Route::post('/produits/{id}/toggle-actif', [FrsProduitController::class, 'toggleActif'])->whereNumber('id');

        Route::put('/frais-livraison', [FrsFraisLivraisonController::class, 'update']);

        Route::get('/profil', [FrsProfileController::class, 'edit']);
        Route::put('/profil', [FrsProfileController::class, 'update']);
        Route::put('/profil/password', [FrsProfileController::class, 'updatePassword']);

        Route::get('/parametres-site', [FrsSiteSettingsController::class, 'edit']);
        Route::put('/parametres-site', [FrsSiteSettingsController::class, 'update']);

        Route::get('/utilisateurs', [FrsUtilisateurController::class, 'index']);
        Route::get('/utilisateurs/create', [FrsUtilisateurController::class, 'create']);
        Route::post('/utilisateurs', [FrsUtilisateurController::class, 'store']);
        Route::get('/utilisateurs/{id}', [FrsUtilisateurController::class, 'show']);
        Route::get('/utilisateurs/{id}/edit', [FrsUtilisateurController::class, 'edit']);
        Route::put('/utilisateurs/{id}', [FrsUtilisateurController::class, 'update']);
        Route::delete('/utilisateurs/{id}', [FrsUtilisateurController::class, 'destroy']);
    });
});
