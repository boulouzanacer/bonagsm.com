<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\ProduitImage;
use App\Services\ImageProduitService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Throwable;

class ProduitController extends Controller
{
    /**
     * Normalise et valide les paliers (min/max/price) et empêche les chevauchements.
     * Important: la validation côté serveur est la source de vérité (le JS est seulement UX).
     */
    private function normalizeQuantityPrices(array $tiers): array
    {
        $normalized = [];

        foreach ($tiers as $row) {
            if (! is_array($row)) {
                continue;
            }

            $min = (int) ($row['quantity_min'] ?? 0);
            $maxRaw = $row['quantity_max'] ?? null;
            $max = ($maxRaw === null || $maxRaw === '') ? null : (int) $maxRaw;
            $price = (float) ($row['price'] ?? -1);

            if ($min <= 0) {
                throw new \InvalidArgumentException('Quantité min invalide.');
            }
            if ($max !== null && $max < $min) {
                throw new \InvalidArgumentException('Quantité max doit être >= quantité min.');
            }
            if ($price < 0) {
                throw new \InvalidArgumentException('Prix invalide.');
            }

            $normalized[] = [
                'quantity_min' => $min,
                'quantity_max' => $max,
                'price' => $price,
            ];
        }

        if (count($normalized) === 0) {
            throw new \InvalidArgumentException('Ajoutez au moins un palier.');
        }

        usort($normalized, fn ($a, $b) => $a['quantity_min'] <=> $b['quantity_min']);

        $prevMax = null;
        foreach ($normalized as $i => $t) {
            if ($i === 0) {
                $prevMax = $t['quantity_max'];
                continue;
            }

            if ($prevMax === null) {
                throw new \InvalidArgumentException('Aucun palier ne peut suivre un palier sans quantité max.');
            }
            if ($t['quantity_min'] <= $prevMax) {
                throw new \InvalidArgumentException('Chevauchement détecté entre paliers.');
            }

            $prevMax = $t['quantity_max'];
        }

        return $normalized;
    }

    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));
        $categorie = trim((string) $request->query('categorie', ''));

        $dbError = null;
        $categories = collect();
        $produits = new LengthAwarePaginator(
            [],
            0,
            18,
            (int) $request->query('page', 1),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        try {
            try {
                $categories = Categorie::query()
                    ->where('id_frs', $frsId)
                    ->orderBy('nom')
                    ->pluck('nom')
                    ->values();
            } catch (QueryException $e) {
                $dbError = 'La base de données n’est pas à jour. Lancez les migrations (php artisan migrate --force).';
            }

            try {
                $produits = Produit::query()
                    ->where('id_frs', $frsId)
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($sub) use ($q) {
                            $sub->where('designation', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%");
                        });
                    })
                    ->when($categorie !== '', fn ($query) => $query->where('categorie', $categorie))
                    ->orderByDesc('created_at')
                    ->paginate(18)
                    ->withQueryString();
            } catch (QueryException $e) {
                $dbError = 'La base de données n’est pas à jour. Lancez les migrations (php artisan migrate --force).';
            }
        } catch (Throwable $e) {
            report($e);
            if ($dbError === null) {
                $dbError = 'Erreur serveur. Consultez le fichier storage/logs/laravel.log.';
            }
        }

        return view('fournisseur.produits.index', [
            'title' => 'Mes Produits',
            'q' => $q,
            'categorie' => $categorie,
            'categories' => $categories,
            'produits' => $produits,
            'db_error' => $dbError,
        ]);
    }

    public function create(): View
    {
        $frsId = (int) session('frs_id');
        $categories = Categorie::query()
            ->where('id_frs', $frsId)
            ->orderBy('nom')
            ->get(['id', 'nom']);

        return view('fournisseur.produits.create', [
            'title' => 'Créer Produit',
            'produit' => null,
            'images' => collect(),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, ImageProduitService $imageService): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'pv_1' => ['required', 'numeric', 'min:0'],
            'pv_2' => ['required', 'numeric', 'min:0'],
            'pv_3' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'categorie_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'abonne_only' => ['nullable', 'boolean'],
            'actif' => ['nullable', 'boolean'],
            'enable_tier_pricing' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images_order' => ['nullable', 'array'],
            'images_order.*' => ['string'],
            'primary_image' => ['nullable', 'string'],
        ]);

        $categorieNom = Categorie::query()
            ->where('id_frs', $frsId)
            ->where('id', (int) $data['categorie_id'])
            ->value('nom');

        if (! $categorieNom) {
            return back()->withErrors(['categorie_id' => 'Catégorie invalide.'])->withInput();
        }
        $data['categorie_nom'] = $categorieNom;

        $enableTier = (int) ($data['enable_tier_pricing'] ?? 0) === 1;
        $tiers = [];
        if ($enableTier) {
            $tierData = $request->validate([
                'quantity_prices' => ['required', 'array', 'min:1'],
                'quantity_prices.*.quantity_min' => ['required', 'integer', 'min:1'],
                'quantity_prices.*.quantity_max' => ['nullable', 'integer', 'min:1'],
                'quantity_prices.*.price' => ['required', 'numeric', 'min:0'],
            ]);

            try {
                $tiers = $this->normalizeQuantityPrices($tierData['quantity_prices']);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['quantity_prices' => $e->getMessage()])->withInput();
            }
        }

        $produit = DB::transaction(function () use ($frsId, $data, $enableTier, $tiers) {
            $produit = Produit::create([
                'id_frs' => $frsId,
                'reference' => $data['reference'],
                'designation' => $data['designation'],
                'description' => $data['description'],
                'pv_1' => $data['pv_1'],
                'pv_2' => $data['pv_2'],
                'pv_3' => $data['pv_3'],
                'stock' => $data['stock'],
                'categorie' => $data['categorie_nom'],
                'abonne_only' => (int) ($data['abonne_only'] ?? 0) === 1 ? 1 : 0,
                'enable_tier_pricing' => $enableTier ? 1 : 0,
                'actif' => (int) ($data['actif'] ?? 0) === 1 ? 1 : 0,
            ]);

            if ($enableTier) {
                foreach ($tiers as $t) {
                    $produit->quantityPrices()->create([
                        'quantity_min' => $t['quantity_min'],
                        'quantity_max' => $t['quantity_max'],
                        'price' => $t['price'],
                    ]);
                }
            }

            return $produit;
        });

        $files = $request->file('images', []);
        if (count($files) > 0) {
            $imageService->storeUploadedImages(
                $produit,
                $frsId,
                $files,
                $data['images_order'] ?? null,
                $data['primary_image'] ?? null
            );
        }

        return redirect()
            ->to("/fournisseur/produits/{$produit->id}/edit")
            ->with('success', 'Produit créé.');
    }

    public function show(int $id): View
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->with('quantityPrices')
            ->findOrFail($id);

        $images = ProduitImage::query()
            ->where('id_produit', $produit->id)
            ->orderBy('ordre')
            ->get();

        return view('fournisseur.produits.show', [
            'title' => 'Détail Produit',
            'produit' => $produit,
            'images' => $images,
        ]);
    }

    public function edit(int $id): View
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->with('quantityPrices')
            ->findOrFail($id);

        $categories = Categorie::query()
            ->where('id_frs', $frsId)
            ->orderBy('nom')
            ->get(['id', 'nom']);

        $images = ProduitImage::query()
            ->where('id_produit', $produit->id)
            ->orderBy('ordre')
            ->get();

        return view('fournisseur.produits.edit', [
            'title' => 'Éditer Produit',
            'produit' => $produit,
            'images' => $images,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, int $id, ImageProduitService $imageService): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $existingCount = ProduitImage::query()->where('id_produit', $produit->id)->count();

        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'pv_1' => ['required', 'numeric', 'min:0'],
            'pv_2' => ['required', 'numeric', 'min:0'],
            'pv_3' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'categorie_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'abonne_only' => ['nullable', 'boolean'],
            'actif' => ['nullable', 'boolean'],
            'enable_tier_pricing' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
            'images_order' => ['nullable', 'array'],
            'images_order.*' => ['string'],
            'primary_image' => ['nullable', 'string'],
        ]);

        $categorieNom = Categorie::query()
            ->where('id_frs', $frsId)
            ->where('id', (int) $data['categorie_id'])
            ->value('nom');

        if (! $categorieNom) {
            return back()->withErrors(['categorie_id' => 'Catégorie invalide.'])->withInput();
        }
        $data['categorie_nom'] = $categorieNom;

        $enableTier = (int) ($data['enable_tier_pricing'] ?? 0) === 1;
        $tiers = [];
        if ($enableTier) {
            $tierData = $request->validate([
                'quantity_prices' => ['required', 'array', 'min:1'],
                'quantity_prices.*.quantity_min' => ['required', 'integer', 'min:1'],
                'quantity_prices.*.quantity_max' => ['nullable', 'integer', 'min:1'],
                'quantity_prices.*.price' => ['required', 'numeric', 'min:0'],
            ]);

            try {
                $tiers = $this->normalizeQuantityPrices($tierData['quantity_prices']);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['quantity_prices' => $e->getMessage()])->withInput();
            }
        }

        DB::transaction(function () use ($produit, $data, $enableTier, $tiers) {
            $produit->update([
                'reference' => $data['reference'],
                'designation' => $data['designation'],
                'description' => $data['description'],
                'pv_1' => $data['pv_1'],
                'pv_2' => $data['pv_2'],
                'pv_3' => $data['pv_3'],
                'stock' => $data['stock'],
                'categorie' => $data['categorie_nom'],
                'abonne_only' => (int) ($data['abonne_only'] ?? 0) === 1 ? 1 : 0,
                'enable_tier_pricing' => $enableTier ? 1 : 0,
                'actif' => (int) ($data['actif'] ?? 0) === 1 ? 1 : 0,
            ]);

            $produit->quantityPrices()->delete();
            if ($enableTier) {
                foreach ($tiers as $t) {
                    $produit->quantityPrices()->create([
                        'quantity_min' => $t['quantity_min'],
                        'quantity_max' => $t['quantity_max'],
                        'price' => $t['price'],
                    ]);
                }
            }
        });

        if (! empty($data['delete_images'] ?? [])) {
            $imageService->deleteImages($produit, $frsId, $data['delete_images']);
            $existingCount = ProduitImage::query()->where('id_produit', $produit->id)->count();
        }

        $files = $request->file('images', []);
        $totalAfter = $existingCount + count($files);
        if ($totalAfter > 5) {
            return back()->withErrors(['images' => 'Maximum 5 images par produit.'])->withInput();
        }

        if (count($files) > 0) {
            $imageService->storeUploadedImages(
                $produit,
                $frsId,
                $files,
                $data['images_order'] ?? null,
                $data['primary_image'] ?? null
            );
        } else {
            $orders = $data['images_order'] ?? null;
            if (is_array($orders)) {
                $existing = ProduitImage::query()
                    ->where('id_produit', $produit->id)
                    ->get()
                    ->keyBy(fn ($img) => 'existing:'.$img->id);

                $ordered = [];
                foreach ($orders as $k) {
                    if (isset($existing[$k])) {
                        $ordered[] = $k;
                    }
                }
                foreach ($existing->keys() as $k) {
                    if (! in_array($k, $ordered, true)) {
                        $ordered[] = $k;
                    }
                }

                foreach ($ordered as $i => $k) {
                    $existing[$k]->update(['ordre' => $i]);
                }
            }

            if (! empty($data['primary_image'] ?? null) && str_starts_with($data['primary_image'], 'existing:')) {
                $idImg = (int) str_replace('existing:', '', $data['primary_image']);
                $img = ProduitImage::query()
                    ->where('id_produit', $produit->id)
                    ->where('id', $idImg)
                    ->first();
                if ($img) {
                    $produit->update(['image_principale' => $img->url_principale]);
                }
            }
        }

        return back()->with('success', 'Produit mis à jour.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $produit->delete();

        return redirect()->to('/fournisseur/produits')->with('success', 'Produit supprimé.');
    }

    public function toggleActif(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $produit->actif = (int) $produit->actif === 1 ? 0 : 1;
        $produit->save();

        return back()->with('success', 'Statut produit mis à jour.');
    }
}
