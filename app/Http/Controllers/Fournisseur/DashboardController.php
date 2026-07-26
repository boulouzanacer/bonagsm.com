<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Cmd1;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\FrsUser;
use App\Models\Produit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');

        $cmdEnAttente = Cmd1::query()
            ->where('id_frs', $frsId)
            ->where('statut', 'en_attente')
            ->count();

        $cmdDuJour = Cmd1::query()
            ->where('id_frs', $frsId)
            ->where('date_cmd', '>=', Carbon::today())
            ->count();

        $clientsAbonnes = Client::query()
            ->where('id_frs', $frsId)
            ->count();

        $produitsActifs = Produit::query()
            ->where('id_frs', $frsId)
            ->where('actif', 1)
            ->count();

        $dernieresCommandes = Cmd1::query()
            ->leftJoin('client', 'client.id', '=', 'cmd1.id_client')
            ->select([
                'cmd1.id',
                'cmd1.date_cmd',
                'cmd1.statut',
                'cmd1.montant_total',
                'client.nom as client_nom',
                'client.prenom as client_prenom',
            ])
            ->where('cmd1.id_frs', $frsId)
            ->orderByDesc('cmd1.date_cmd')
            ->limit(5)
            ->get();

        $ruptureStock = Produit::query()
            ->where('id_frs', $frsId)
            ->where('stock', '<', 5)
            ->orderBy('stock')
            ->limit(5)
            ->get(['id', 'reference', 'designation', 'stock', 'pv_1', 'actif', 'image_principale']);

        $databaseToolsUnlocked = $this->hasDatabaseToolsAccess($request);
        $databaseTables = $this->getDatabaseTables($frsId);

        return view('fournisseur.dashboard', [
            'title' => 'Mon Dashboard',
            'cmd_en_attente' => $cmdEnAttente,
            'cmd_du_jour' => $cmdDuJour,
            'clients_abonnes' => $clientsAbonnes,
            'produits_actifs' => $produitsActifs,
            'dernieres_commandes' => $dernieresCommandes,
            'rupture_stock' => $ruptureStock,
            'database_tools_unlocked' => $databaseToolsUnlocked,
            'database_tables' => $databaseTables,
        ]);
    }

    public function unlockDatabaseTools(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'database_password' => ['required', 'string'],
        ], [
            'database_password.required' => __('Veuillez saisir le mot de passe.'),
        ]);

        $hashedPassword = $this->getCurrentAdminPasswordHash($request);
        if ($hashedPassword === null || ! Hash::check((string) $data['database_password'], $hashedPassword)) {
            return $this->redirectToDatabaseTools()
                ->withErrors(['database_password' => __('Mot de passe incorrect.')]);
        }

        $request->session()->put([
            'dashboard_db_tools_unlocked' => true,
            'dashboard_db_tools_unlocked_at' => now()->timestamp,
        ]);

        return $this->redirectToDatabaseTools()
            ->with('success', __('Accès base de données activé pour cette session.'));
    }

    public function resetDatabaseTables(Request $request): RedirectResponse
    {
        if (! $this->hasDatabaseToolsAccess($request)) {
            return $this->redirectToDatabaseTools()
                ->withErrors(['database_password' => __('Veuillez confirmer votre mot de passe avant de réinitialiser une table.')]);
        }

        $allowedTables = array_keys($this->getDatabaseTables((int) session('frs_id')));
        $data = $request->validate([
            'tables' => ['required', 'array', 'min:1'],
            'tables.*' => ['required', 'string', 'in:'.implode(',', $allowedTables)],
        ], [
            'tables.required' => __('Sélectionnez au moins une table.'),
            'tables.min' => __('Sélectionnez au moins une table.'),
            'tables.*.in' => __('Une table sélectionnée est invalide.'),
        ]);

        $frsId = (int) session('frs_id');
        $selectedTables = array_values(array_unique(array_map('strval', $data['tables'])));
        $orderedTables = $this->sortTablesForReset($selectedTables);
        $summary = [];

        try {
            DB::transaction(function () use ($orderedTables, $frsId, &$summary) {
                foreach ($orderedTables as $table) {
                    $deletedRows = $this->resetTableData($table, $frsId, $orderedTables);
                    $summary[] = sprintf('%s: %d', $table, $deletedRows);
                }
            });
        } catch (ValidationException $exception) {
            return $this->redirectToDatabaseTools()
                ->withErrors($exception->errors())
                ->withInput();
        } catch (\Throwable $exception) {
            return $this->redirectToDatabaseTools()
                ->withErrors(['tables' => __('Réinitialisation impossible : :message', ['message' => $exception->getMessage()])])
                ->withInput();
        }

        return $this->redirectToDatabaseTools()
            ->with('success', __('Réinitialisation terminée. :summary', ['summary' => implode(' | ', $summary)]));
    }

    private function getDatabaseTables(int $frsId): array
    {
        return [
            'cmd1' => [
                'label' => 'cmd1',
                'title' => __('Commandes'),
                'description' => __('Supprime les commandes du fournisseur et leurs lignes liées dans cmd2.'),
                'count' => (int) DB::table('cmd1')->where('id_frs', $frsId)->count(),
            ],
            'client' => [
                'label' => 'client',
                'title' => __('Clients'),
                'description' => __('Supprime les clients du fournisseur et leur wishlist. Nécessite cmd1 vide ou sélectionné.'),
                'count' => (int) DB::table('client')->where('id_frs', $frsId)->count(),
            ],
            'produit' => [
                'label' => 'produit',
                'title' => __('Produits'),
                'description' => __('Supprime les produits, images, tarifs par quantité et wishlist. Nécessite cmd1 vide ou sélectionné.'),
                'count' => (int) DB::table('produit')->where('id_frs', $frsId)->count(),
            ],
            'categories' => [
                'label' => 'categories',
                'title' => __('Catégories'),
                'description' => __('Supprime les catégories et retire leur affectation des produits.'),
                'count' => (int) DB::table('categories')->where('id_frs', $frsId)->count(),
            ],
            'sous_categories' => [
                'label' => 'sous_categories',
                'title' => __('Sous-catégories'),
                'description' => __('Supprime les sous-catégories et retire leur affectation des produits.'),
                'count' => (int) DB::table('sous_categories')->where('id_frs', $frsId)->count(),
            ],
            'marques' => [
                'label' => 'marques',
                'title' => __('Marques'),
                'description' => __('Supprime les marques et retire leur affectation des produits.'),
                'count' => (int) DB::table('marques')->where('id_frs', $frsId)->count(),
            ],
            'frais_livraison' => [
                'label' => 'frais_livraison',
                'title' => __('Frais de livraison'),
                'description' => __('Supprime tous les frais de livraison configurés pour ce fournisseur.'),
                'count' => (int) DB::table('frais_livraison')->where('id_frs', $frsId)->count(),
            ],
        ];
    }

    private function sortTablesForReset(array $selectedTables): array
    {
        $priority = [
            'cmd1' => 10,
            'client' => 20,
            'produit' => 30,
            'sous_categories' => 40,
            'categories' => 50,
            'marques' => 60,
            'frais_livraison' => 70,
        ];

        usort($selectedTables, function (string $left, string $right) use ($priority): int {
            return ($priority[$left] ?? 999) <=> ($priority[$right] ?? 999);
        });

        return $selectedTables;
    }

    private function resetTableData(string $table, int $frsId, array $selectedTables): int
    {
        return match ($table) {
            'cmd1' => $this->resetOrders($frsId),
            'client' => $this->resetClients($frsId, $selectedTables),
            'produit' => $this->resetProducts($frsId, $selectedTables),
            'categories' => $this->resetCategories($frsId),
            'sous_categories' => $this->resetSubCategories($frsId),
            'marques' => $this->resetBrands($frsId),
            'frais_livraison' => (int) DB::table('frais_livraison')->where('id_frs', $frsId)->delete(),
            default => throw ValidationException::withMessages([
                'tables' => __('Table non prise en charge : :table', ['table' => $table]),
            ]),
        };
    }

    private function resetOrders(int $frsId): int
    {
        $orderIds = DB::table('cmd1')
            ->where('id_frs', $frsId)
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return 0;
        }

        DB::table('cmd2')->whereIn('id_cmd', $orderIds)->delete();

        return (int) DB::table('cmd1')->whereIn('id', $orderIds)->delete();
    }

    private function resetClients(int $frsId, array $selectedTables): int
    {
        $hasOrders = DB::table('cmd1')->where('id_frs', $frsId)->exists();
        if ($hasOrders && ! in_array('cmd1', $selectedTables, true)) {
            throw ValidationException::withMessages([
                'tables' => __('La table client nécessite de vider cmd1 d’abord, ou de sélectionner cmd1 dans la même action.'),
            ]);
        }

        $clientIds = DB::table('client')
            ->where('id_frs', $frsId)
            ->pluck('id');

        if ($clientIds->isEmpty()) {
            return 0;
        }

        DB::table('client_wishlist')->whereIn('id_client', $clientIds)->delete();

        return (int) DB::table('client')->whereIn('id', $clientIds)->delete();
    }

    private function resetProducts(int $frsId, array $selectedTables): int
    {
        $hasOrders = DB::table('cmd1')->where('id_frs', $frsId)->exists();
        if ($hasOrders && ! in_array('cmd1', $selectedTables, true)) {
            throw ValidationException::withMessages([
                'tables' => __('La table produit nécessite de vider cmd1 d’abord, ou de sélectionner cmd1 dans la même action.'),
            ]);
        }

        $productIds = DB::table('produit')
            ->where('id_frs', $frsId)
            ->pluck('id');

        if ($productIds->isEmpty()) {
            return 0;
        }

        DB::table('produit_quantity_prices')->whereIn('id_produit', $productIds)->delete();
        DB::table('produit_images')->whereIn('id_produit', $productIds)->delete();
        DB::table('client_wishlist')->whereIn('id_produit', $productIds)->delete();

        return (int) DB::table('produit')->whereIn('id', $productIds)->delete();
    }

    private function resetCategories(int $frsId): int
    {
        DB::table('produit')
            ->where('id_frs', $frsId)
            ->update([
                'id_categorie' => null,
                'categorie' => null,
            ]);

        return (int) DB::table('categories')->where('id_frs', $frsId)->delete();
    }

    private function resetSubCategories(int $frsId): int
    {
        DB::table('produit')
            ->where('id_frs', $frsId)
            ->update([
                'id_sous_categorie' => null,
            ]);

        return (int) DB::table('sous_categories')->where('id_frs', $frsId)->delete();
    }

    private function resetBrands(int $frsId): int
    {
        DB::table('produit')
            ->where('id_frs', $frsId)
            ->update([
                'id_marque' => null,
            ]);

        return (int) DB::table('marques')->where('id_frs', $frsId)->delete();
    }

    private function hasDatabaseToolsAccess(Request $request): bool
    {
        if (! $request->session()->get('dashboard_db_tools_unlocked', false)) {
            return false;
        }

        $unlockedAt = (int) $request->session()->get('dashboard_db_tools_unlocked_at', 0);
        if ($unlockedAt < now()->subMinutes(15)->timestamp) {
            $request->session()->forget(['dashboard_db_tools_unlocked', 'dashboard_db_tools_unlocked_at']);

            return false;
        }

        return true;
    }

    private function getCurrentAdminPasswordHash(Request $request): ?string
    {
        $role = (string) $request->session()->get('role', '');
        $frsId = (int) $request->session()->get('frs_id', 0);

        if ($role === 'fournisseur') {
            return Fournisseur::query()
                ->where('id', $frsId)
                ->value('password');
        }

        if ($role === 'frs_user' && (int) $request->session()->get('is_admin', 0) === 1) {
            return FrsUser::query()
                ->where('id', (int) $request->session()->get('frs_user_id', 0))
                ->where('id_frs', $frsId)
                ->value('password');
        }

        return null;
    }

    private function redirectToDatabaseTools(): RedirectResponse
    {
        return redirect()->to('/fournisseur/dashboard#database-tools');
    }
}
