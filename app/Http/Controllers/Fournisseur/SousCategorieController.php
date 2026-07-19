<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\SousCategorie;
use App\Models\Produit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SousCategorieController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));

        $sousCategories = SousCategorie::query()
            ->with('categorie')
            ->where('id_frs', $frsId)
            ->when($q !== '', fn ($query) => $query->where('nom', 'like', "%{$q}%"))
            ->orderBy('nom')
            ->paginate(20)
            ->withQueryString();

        return view('fournisseur.sous_categories.index', [
            'title' => 'Sous-catégories',
            'q' => $q,
            'sousCategories' => $sousCategories,
        ]);
    }

    public function create(): View
    {
        $frsId = (int) session('frs_id');
        $categories = Categorie::query()->where('id_frs', $frsId)->orderBy('nom')->get();

        return view('fournisseur.sous_categories.create', [
            'title' => 'Créer sous-catégorie',
            'sousCategorie' => null,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $data = $request->validate([
            'id_categorie' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sous_categories', 'nom')
                    ->where(fn ($q) => $q->where('id_frs', $frsId)->where('id_categorie', $request->id_categorie)),
            ],
        ]);

        SousCategorie::create([
            'id_frs' => $frsId,
            'id_categorie' => $data['id_categorie'],
            'nom' => trim($data['nom']),
        ]);

        return redirect()->to('/fournisseur/sous-categories')->with('success', __('Sous-catégorie créée.'));
    }

    public function edit(int $id): View
    {
        $frsId = (int) session('frs_id');

        $sousCategorie = SousCategorie::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $categories = Categorie::query()->where('id_frs', $frsId)->orderBy('nom')->get();

        return view('fournisseur.sous_categories.edit', [
            'title' => 'Éditer sous-catégorie',
            'sousCategorie' => $sousCategorie,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $sousCategorie = SousCategorie::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $data = $request->validate([
            'id_categorie' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sous_categories', 'nom')
                    ->where(fn ($q) => $q->where('id_frs', $frsId)->where('id_categorie', $request->id_categorie))
                    ->ignore($sousCategorie->id),
            ],
        ]);

        $sousCategorie->update([
            'id_categorie' => $data['id_categorie'],
            'nom' => trim($data['nom']),
        ]);

        return redirect()->to('/fournisseur/sous-categories')->with('success', __('Sous-catégorie mise à jour.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $sousCategorie = SousCategorie::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $used = Produit::query()
            ->where('id_frs', $frsId)
            ->where('id_sous_categorie', $sousCategorie->id)
            ->exists();

        if ($used) {
            return back()->with('error', __('Impossible de supprimer: sous-catégorie utilisée par des produits.'));
        }

        $sousCategorie->delete();

        return back()->with('success', __('Sous-catégorie supprimée.'));
    }
}
