<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Cmd1;
use App\Models\Client;
use App\Models\Produit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(): View
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

        return view('fournisseur.dashboard', [
            'title' => 'Mon Dashboard',
            'cmd_en_attente' => $cmdEnAttente,
            'cmd_du_jour' => $cmdDuJour,
            'clients_abonnes' => $clientsAbonnes,
            'produits_actifs' => $produitsActifs,
            'dernieres_commandes' => $dernieresCommandes,
            'rupture_stock' => $ruptureStock,
        ]);
    }
}
