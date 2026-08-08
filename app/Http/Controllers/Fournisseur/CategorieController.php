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

        $categories = Categorie::query()
            ->where('categories.id_frs', $frsId)
            ->when($q !== '', fn ($query) => $query->where('categories.nom', 'like', "%{$q}%"))
            ->orderBy('categories.nom')
            ->paginate(20)
            ->withQueryString();

        if ($categories->isNotEmpty()) {
            $ids = $categories->map(fn ($c) => (int) $c->id)->all();

            $productsByIdCounts = DB::table('produit')
                ->where('id_frs', $frsId)
                ->whereNull('deleted_at')
                ->whereIn('id_categorie', $ids)
                ->groupBy('id_categorie')
                ->pluck(DB::raw('COUNT(*)'), 'id_categorie')
                ->all();

            $productsByNameCounts = [];
            $catNames = $categories->mapWithKeys(fn ($c) => [$c->id => (string) $c->nom])->all();
            if ($catNames !== []) {
                $nameRows = DB::table('produit')
                    ->select(['categorie', DB::raw('COUNT(*) as cnt')])
                    ->where('id_frs', $frsId)
                    ->whereNull('deleted_at')
                    ->whereIn('categorie', array_values(array_values($catNames)))
                    ->groupBy('categorie')
                    ->get()
                    ->all();
                $nameToCount = [];
                foreach ($nameRows as $row) {
                    $nameToCount[(string) $row->categorie] = (int) ($row->cnt ?? 0);
                }
                foreach ($catNames as $cid => $nom) {
                    if (isset($nameToCount[$nom])) {
                        $productsByNameCounts[$cid] = $nameToCount[$nom];
                    }
                }
            }

            $subCategoriesCounts = DB::table('sous_categories')
                ->where('id_frs', $frsId)
                ->whereIn('id_categorie', $ids)
                ->groupBy('id_categorie')
                ->pluck(DB::raw('COUNT(*)'), 'id_categorie')
                ->all();

            foreach ($categories as $c) {
                $cid = (int) $c->id;
                $byId = (int) ($productsByIdCounts[$cid] ?? 0);
                $byName = (int) ($productsByNameCounts[$cid] ?? 0);
                $usedProducts = max($byId, $byName);
                $usedSub = (int) ($subCategoriesCounts[$cid] ?? 0);
                $c->setAttribute('used_products_count', $usedProducts);
                $c->setAttribute('used_sub_categories_count', $usedSub);
                $c->setAttribute('can_delete', $usedProducts === 0 && $usedSub === 0);
            }
        } else {
            foreach ($categories as $c) {
                $c->setAttribute('used_products_count', 0);
                $c->setAttribute('used_sub_categories_count', 0);
                $c->setAttribute('can_delete', true);
            }
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
