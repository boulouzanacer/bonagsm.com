<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Cmd1;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));

        $cmdCounts = DB::table('cmd1')
            ->selectRaw('id_client, COUNT(*) as nb')
            ->where('id_frs', $frsId)
            ->groupBy('id_client');

        $clients = Client::query()
            ->leftJoin('commune', 'commune.ID_COMMUNE', '=', 'client.id_commune')
            ->leftJoinSub($cmdCounts, 'cc', function ($join) {
                $join->on('cc.id_client', '=', 'client.id');
            })
            ->select([
                'client.*',
                'commune.COMMUNE as commune_nom',
                DB::raw('COALESCE(cc.nb, 0) as nb_commandes'),
            ])
            ->where('client.id_frs', $frsId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('client.nom', 'like', "%{$q}%")
                        ->orWhere('client.prenom', 'like', "%{$q}%")
                        ->orWhere('client.email', 'like', "%{$q}%")
                        ->orWhere('client.code_client', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('client.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('fournisseur.clients.index', [
            'title' => 'Mes Clients',
            'q' => $q,
            'clients' => $clients,
        ]);
    }

    public function show(int $id): View
    {
        $frsId = (int) session('frs_id');

        $client = Client::query()
            ->where('id', $id)
            ->where('id_frs', $frsId)
            ->firstOrFail();

        $commandes = Cmd1::query()
            ->where('id_frs', $frsId)
            ->where('id_client', $client->id)
            ->orderByDesc('date_cmd')
            ->paginate(10);

        return view('fournisseur.clients.show', [
            'title' => 'Détail Client',
            'client' => $client,
            'commandes' => $commandes,
        ]);
    }

    public function updateTarif(Request $request, int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $data = $request->validate([
            'tarif' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $client = Client::query()
            ->where('id', $id)
            ->where('id_frs', $frsId)
            ->firstOrFail();

        $client->update(['tarif' => (int) $data['tarif']]);

        return back()->with('success', __('Tarif mis à jour.'));
    }
}
