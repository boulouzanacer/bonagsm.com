<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\SousCategorie;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategorieController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));

        $productsByIdExpr = DB::raw('COALESCE((
            SELECT COUNT(*)
            FROM produit pbyid
            WHERE pbyid.id_frs = categories.id_frs
              AND pbyid.id_categorie = categories.id
              AND pbyid.deleted_at IS NULL
        ), 0)');

        $productsByNameExpr = DB::raw('COALESCE((
            SELECT COUNT(*)
            FROM produit pbyname
            WHERE pbyname.id_frs = categories.id_frs
              AND pbyname.categorie = categories.nom
              AND pbyname.deleted_at IS NULL
        ), 0)');

        $subCategoriesExpr = DB::raw('COALESCE((
            SELECT COUNT(*)
            FROM sous_categories sc
            WHERE sc.id_frs = categories.id_frs
              AND sc.id_categorie = categories.id
        ), 0)');

        $categories = Categorie::query()
            ->select('categories.*')
            ->selectSub($productsByIdExpr, 'products_by_id_count')
            ->selectSub($productsByNameExpr, 'products_by_name_count')
            ->selectSub($subCategoriesExpr, 'sub_categories_count')
            ->where('categories.id_frs', $frsId)
            ->when($q !== '', fn ($query) => $query->where('categories.nom', 'like', "%{$q}%"))
            ->orderBy('categories.nom')
            ->paginate(20)
            ->withQueryString();

        foreach ($categories as $c) {
            $c->setAttribute('used_products_count', (int) max(
                (int) ($c->products_by_id_count ?? 0),
                (int) ($c->products_by_name_count ?? 0)
            ));
            $c->setAttribute('used_sub_categories_count', (int) ($c->sub_categories_count ?? 0));
            $c->setAttribute('can_delete', (int) $c->used_products_count === 0 && (int) $c->used_sub_categories_count === 0);
        }

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

        $productsById = Produit::query()
            ->where('id_frs', $frsId)
            ->where('id_categorie', $categorie->id)
            ->count();

        $productsByName = Produit::query()
            ->where('id_frs', $frsId)
            ->where('categorie', $categorie->nom)
            ->count();

        $usedProducts = (int) max($productsById, $productsByName);
        $usedSubCategories = (int) \App\Models\SousCategorie::query()
            ->where('id_frs', $frsId)
            ->where('id_categorie', $categorie->id)
            ->count();

        if ($usedProducts > 0 || $usedSubCategories > 0) {
            $parts = [];
            if ($usedProducts > 0) {
                $parts[] = __('produits: :count', ['count' => $usedProducts]);
            }
            if ($usedSubCategories > 0) {
                $parts[] = __('sous-catégories: :count', ['count' => $usedSubCategories]);
            }

            return back()->with('error', __('Impossible de supprimer: catégorie utilisée par :list.', ['list' => implode(', ', $parts)]));
        }

        $categorie->delete();

        return back()->with('success', __('Catégorie supprimée.'));
    }
}
