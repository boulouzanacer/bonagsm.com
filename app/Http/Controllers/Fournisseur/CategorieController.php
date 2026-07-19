<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategorieController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));

        $categories = Categorie::query()
            ->where('id_frs', $frsId)
            ->when($q !== '', fn ($query) => $query->where('nom', 'like', "%{$q}%"))
            ->orderBy('nom')
            ->paginate(20)
            ->withQueryString();

        return view('fournisseur.categories.index', [
            'title' => 'Catégories',
            'q' => $q,
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('fournisseur.categories.create', [
            'title' => 'Créer catégorie',
            'categorie' => null,
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
                Rule::unique('categories', 'nom')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
        ]);

        $name = trim((string) $data['nom']);
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = Str::slug('categorie-'.$name);
        }

        Categorie::create([
            'id_frs' => $frsId,
            'nom' => $name,
            'slug' => $slug,
        ]);

        return redirect()->to('/fournisseur/categories')->with('success', __('Catégorie créée.'));
    }

    public function edit(int $id): View
    {
        $frsId = (int) session('frs_id');

        $categorie = Categorie::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        return view('fournisseur.categories.edit', [
            'title' => 'Éditer catégorie',
            'categorie' => $categorie,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $categorie = Categorie::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'nom')->where(fn ($q) => $q->where('id_frs', $frsId))->ignore($categorie->id),
            ],
        ]);

        $oldName = (string) $categorie->nom;
        $name = trim((string) $data['nom']);
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = Str::slug('categorie-'.$name);
        }

        $categorie->update([
            'nom' => $name,
            'slug' => $slug,
        ]);

        Produit::query()
            ->where('id_frs', $frsId)
            ->where('categorie', $oldName)
            ->update(['categorie' => $name]);

        return back()->with('success', __('Catégorie mise à jour.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $categorie = Categorie::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $used = Produit::query()
            ->where('id_frs', $frsId)
            ->where('categorie', $categorie->nom)
            ->exists();

        if ($used) {
            return back()->with('error', __('Impossible de supprimer: catégorie utilisée par des produits.'));
        }

        $categorie->delete();

        return back()->with('success', __('Catégorie supprimée.'));
    }
}
