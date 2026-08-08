<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Marque;
use App\Models\Produit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarqueController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));

        $productsCountExpr = DB::raw('COALESCE((
            SELECT COUNT(*)
            FROM produit p
            WHERE p.id_frs = marques.id_frs
              AND p.id_marque = marques.id
              AND p.deleted_at IS NULL
        ), 0)');

        $marques = Marque::query()
            ->select('marques.*')
            ->selectSub($productsCountExpr, 'products_count')
            ->where('marques.id_frs', $frsId)
            ->when($q !== '', fn ($query) => $query->where('marques.nom', 'like', "%{$q}%"))
            ->orderBy('marques.nom')
            ->paginate(20)
            ->withQueryString();

        foreach ($marques as $m) {
            $m->setAttribute('used_products_count', (int) ($m->products_count ?? 0));
            $m->setAttribute('can_delete', (int) $m->used_products_count === 0);
        }

        return view('fournisseur.marques.index', [
            'title' => 'Marques',
            'q' => $q,
            'marques' => $marques,
        ]);
    }

    public function create(): View
    {
        return view('fournisseur.marques.create', [
            'title' => 'Créer marque',
            'marque' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marques', 'nom')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
        ]);

        Marque::create([
            'id_frs' => $frsId,
            'nom' => trim($data['nom']),
        ]);

        return redirect()->to('/fournisseur/marques')->with('success', __('Marque créée.'));
    }

    public function edit(int $id): View
    {
        $frsId = (int) session('frs_id');

        $marque = Marque::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        return view('fournisseur.marques.edit', [
            'title' => 'Éditer marque',
            'marque' => $marque,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $marque = Marque::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marques', 'nom')->where(fn ($q) => $q->where('id_frs', $frsId))->ignore($marque->id),
            ],
        ]);

        $marque->update([
            'nom' => trim($data['nom']),
        ]);

        return redirect()->to('/fournisseur/marques')->with('success', __('Marque mise à jour.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $marque = Marque::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $usedProducts = (int) Produit::query()
            ->where('id_frs', $frsId)
            ->where('id_marque', $marque->id)
            ->count();

        if ($usedProducts > 0) {
            return back()->with('error', __('Impossible de supprimer: marque utilisée par produits: :count.', ['count' => $usedProducts]));
        }

        $marque->delete();

        return back()->with('success', __('Marque supprimée.'));
    }
}
