<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\ProduitImage;
use App\Services\ImageProduitService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Support\Str;

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
                throw new \InvalidArgumentException(__('Quantité min invalide.'));
            }
            if ($max !== null && $max < $min) {
                throw new \InvalidArgumentException(__('Quantité max doit être >= quantité min.'));
            }
            if ($price < 0) {
                throw new \InvalidArgumentException(__('Prix invalide.'));
            }

            $normalized[] = [
                'quantity_min' => $min,
                'quantity_max' => $max,
                'price' => $price,
            ];
        }

        if (count($normalized) === 0) {
            throw new \InvalidArgumentException(__('Ajoutez au moins un palier.'));
        }

        usort($normalized, fn ($a, $b) => $a['quantity_min'] <=> $b['quantity_min']);

        $prevMax = null;
        foreach ($normalized as $i => $t) {
            if ($i === 0) {
                $prevMax = $t['quantity_max'];
                continue;
            }

            if ($prevMax === null) {
                throw new \InvalidArgumentException(__('Aucun palier ne peut suivre un palier sans quantité max.'));
            }
            if ($t['quantity_min'] <= $prevMax) {
                throw new \InvalidArgumentException(__('Chevauchement détecté entre paliers.'));
            }

            $prevMax = $t['quantity_max'];
        }

        return $normalized;
    }

    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));
        $categorieId = $request->query('id_categorie');
        $subCatId = $request->query('id_sous_categorie');
        $marqueId = $request->query('id_marque');

        $dbError = null;
        $categories = collect();
        $sousCategories = collect();
        $marques = collect();
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
                    ->get(['id', 'nom']);

                $marques = \App\Models\Marque::query()
                    ->where('id_frs', $frsId)
                    ->orderBy('nom')
                    ->get(['id', 'nom']);

                if ($categorieId) {
                    $sousCategories = \App\Models\SousCategorie::query()
                        ->where('id_frs', $frsId)
                        ->where('id_categorie', $categorieId)
                        ->orderBy('nom')
                        ->get(['id', 'nom']);
                }
            } catch (QueryException $e) {
                $dbError = __('La base de données n’est pas à jour. Lancez les migrations (php artisan migrate --force).');
            }

            try {
                $produits = Produit::query()
                    ->with(['category', 'subCategory', 'brand'])
                    ->where('id_frs', $frsId)
                    ->when($q !== '', function ($query) use ($q) {
                        $query->where(function ($sub) use ($q) {
                            $sub->where('designation', 'like', "%{$q}%")
                                ->orWhere('reference', 'like', "%{$q}%");
                        });
                    })
                    ->when($categorieId, fn ($query) => $query->where('id_categorie', $categorieId))
                    ->when($subCatId, fn ($query) => $query->where('id_sous_categorie', $subCatId))
                    ->when($marqueId, fn ($query) => $query->where('id_marque', $marqueId))
                    ->orderByDesc('created_at')
                    ->paginate(18)
                    ->withQueryString();
            } catch (QueryException $e) {
                $dbError = __('La base de données n’est pas à jour. Lancez les migrations (php artisan migrate --force).');
            }
        } catch (Throwable $e) {
            report($e);
            if ($dbError === null) {
                $dbError = __('Erreur serveur. Consultez le fichier storage/logs/laravel.log.');
            }
        }

        return view('fournisseur.produits.index', [
            'title' => 'Mes Produits',
            'q' => $q,
            'categories' => $categories,
            'sousCategories' => $sousCategories,
            'marques' => $marques,
            'id_categorie' => $categorieId,
            'id_sous_categorie' => $subCatId,
            'id_marque' => $marqueId,
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

        $sousCategories = \App\Models\SousCategorie::query()
            ->where('id_frs', $frsId)
            ->orderBy('nom')
            ->get(['id', 'id_categorie', 'nom']);

        $marques = \App\Models\Marque::query()
            ->where('id_frs', $frsId)
            ->orderBy('nom')
            ->get(['id', 'nom']);

        return view('fournisseur.produits.create', [
            'title' => 'Créer Produit',
            'produit' => null,
            'images' => collect(),
            'categories' => $categories,
            'sousCategories' => $sousCategories,
            'marques' => $marques,
        ]);
    }

    public function store(Request $request, ImageProduitService $imageService): RedirectResponse
    {
        $frsId = (int) session('frs_id');
        $isAdmin = (int) session('is_admin', 0) === 1 || (string) session('role', '') === 'fournisseur';

        $request->merge([
            'reference' => trim((string) $request->input('reference', '')),
        ]);

        $data = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:100',
                Rule::unique('produit', 'reference')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'pv_1' => ['required', 'numeric', 'min:0'],
            'pv_2' => ['required', 'numeric', 'min:0'],
            'pv_3' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'id_categorie' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'id_sous_categorie' => [
                'nullable',
                'integer',
                Rule::exists('sous_categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)->where('id_categorie', $request->id_categorie)),
            ],
            'id_marque' => [
                'nullable',
                'integer',
                Rule::exists('marques', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'abonne_only' => ['nullable', 'boolean'],
            'actif' => ['nullable', 'boolean'],
            'enable_tier_pricing' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images_order' => ['nullable', 'array'],
            'images_order.*' => ['string'],
            'primary_image' => ['nullable', 'string'],
        ], [
            'reference.unique' => __('Référence déjà utilisée.'),
        ]);

        $categorieNom = Categorie::query()
            ->where('id_frs', $frsId)
            ->where('id', (int) $data['id_categorie'])
            ->value('nom');

        if (! $categorieNom) {
            return back()->withErrors(['id_categorie' => __('Catégorie invalide.')])->withInput();
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
                'id_categorie' => $data['id_categorie'],
                'id_sous_categorie' => $data['id_sous_categorie'] ?? null,
                'id_marque' => $data['id_marque'] ?? null,
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
            ->to($isAdmin ? "/fournisseur/produits/{$produit->id}/edit" : "/fournisseur/produits/{$produit->id}")
            ->with('success', __('Produit créé.'));
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

        $sousCategories = \App\Models\SousCategorie::query()
            ->where('id_frs', $frsId)
            ->orderBy('nom')
            ->get(['id', 'id_categorie', 'nom']);

        $marques = \App\Models\Marque::query()
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
            'sousCategories' => $sousCategories,
            'marques' => $marques,
        ]);
    }

    public function update(Request $request, int $id, ImageProduitService $imageService): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $request->merge([
            'reference' => trim((string) $request->input('reference', '')),
        ]);

        $existingCount = ProduitImage::query()->where('id_produit', $produit->id)->count();

        $referenceRules = ['required', 'string', 'max:100'];
        if ((string) $request->input('reference') !== (string) $produit->reference) {
            $referenceRules[] = Rule::unique('produit', 'reference')
                ->where(fn ($q) => $q->where('id_frs', $frsId))
                ->ignore($produit->id, 'id');
        }

        $data = $request->validate([
            'reference' => $referenceRules,
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'pv_1' => ['required', 'numeric', 'min:0'],
            'pv_2' => ['required', 'numeric', 'min:0'],
            'pv_3' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'id_categorie' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'id_sous_categorie' => [
                'nullable',
                'integer',
                Rule::exists('sous_categories', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)->where('id_categorie', $request->id_categorie)),
            ],
            'id_marque' => [
                'nullable',
                'integer',
                Rule::exists('marques', 'id')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
            'abonne_only' => ['nullable', 'boolean'],
            'actif' => ['nullable', 'boolean'],
            'enable_tier_pricing' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
            'images_order' => ['nullable', 'array'],
            'images_order.*' => ['string'],
            'primary_image' => ['nullable', 'string'],
        ], [
            'reference.unique' => __('Référence déjà utilisée.'),
        ]);

        $categorieNom = Categorie::query()
            ->where('id_frs', $frsId)
            ->where('id', (int) $data['id_categorie'])
            ->value('nom');

        if (! $categorieNom) {
            return back()->withErrors(['id_categorie' => __('Catégorie invalide.')])->withInput();
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
                'id_categorie' => $data['id_categorie'],
                'id_sous_categorie' => $data['id_sous_categorie'] ?? null,
                'id_marque' => $data['id_marque'] ?? null,
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
            return back()->withErrors(['images' => __('Maximum 5 images par produit.')])->withInput();
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

        return back()->with('success', __('Produit mis à jour.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $produit->delete();

        return redirect()->to('/fournisseur/produits')->with('success', __('Produit supprimé.'));
    }

    public function import(Request $request): JsonResponse|RedirectResponse
    {
        $frsId = (int) session('frs_id');
        $isAdmin = (int) session('is_admin', 0) === 1 || (string) session('role', '') === 'fournisseur';

        $data = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.reference' => ['required', 'string', 'max:255'],
            'mapping.designation' => ['nullable', 'string', 'max:255'],
            'mapping.description' => ['nullable', 'string', 'max:255'],
            'mapping.pv_1' => ['nullable', 'string', 'max:255'],
            'mapping.pv_2' => ['nullable', 'string', 'max:255'],
            'mapping.pv_3' => ['nullable', 'string', 'max:255'],
            'mapping.stock' => ['nullable', 'string', 'max:255'],
            'mapping.categorie' => ['nullable', 'string', 'max:255'],
            'mapping.sous_categorie' => ['nullable', 'string', 'max:255'],
            'mapping.marque' => ['nullable', 'string', 'max:255'],
            'update' => ['nullable', 'array'],
            'update.*' => ['string', Rule::in(['designation', 'description', 'pv_1', 'pv_2', 'pv_3', 'stock', 'categorie', 'sous_categorie', 'marque'])],
            'stock_mode' => ['nullable', 'string', Rule::in(['replace', 'add'])],
            'rows' => ['required', 'array', 'min:1', 'max:2000'],
            'rows.*' => ['array'],
        ]);

        $mapping = $data['mapping'];
        $update = $isAdmin ? collect($data['update'] ?? [])->values()->all() : [];
        $stockMode = (string) ($data['stock_mode'] ?? 'replace');
        $rows = $data['rows'];

        $refKey = (string) $mapping['reference'];

        $refs = collect($rows)
            ->map(fn ($r) => is_array($r) ? trim((string) ($r[$refKey] ?? '')) : '')
            ->filter(fn ($v) => $v !== '')
            ->map(fn ($v) => mb_substr($v, 0, 100))
            ->values()
            ->all();

        if (count($refs) === 0) {
            return response()->json([
                'success' => false,
                'message' => __('Aucune référence valide trouvée dans le fichier.'),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
            ], 422);
        }

        $existing = Produit::query()
            ->where('id_frs', $frsId)
            ->whereIn('reference', $refs)
            ->get(['id', 'reference', 'stock'])
            ->keyBy('reference');

        $created = 0;
        $updatedCount = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use (
            $rows,
            $mapping,
            $refKey,
            $frsId,
            $existing,
            $update,
            $stockMode,
            $isAdmin,
            &$created,
            &$updatedCount,
            &$skipped,
            &$errors
        ) {
            $catCache = Categorie::query()
                ->where('id_frs', $frsId)
                ->pluck('id', 'nom')
                ->all();

            $subCatCache = \App\Models\SousCategorie::query()
                ->where('id_frs', $frsId)
                ->get(['id', 'id_categorie', 'nom'])
                ->groupBy('id_categorie')
                ->map(fn ($items) => $items->pluck('id', 'nom')->all())
                ->all();

            $marqueCache = \App\Models\Marque::query()
                ->where('id_frs', $frsId)
                ->pluck('id', 'nom')
                ->all();

            foreach ($rows as $i => $row) {
                if (! is_array($row)) {
                    $skipped++;
                    continue;
                }

                $reference = trim((string) ($row[$refKey] ?? ''));
                $reference = mb_substr($reference, 0, 100);
                if ($reference === '') {
                    $skipped++;
                    continue;
                }

                $get = function (string $field) use ($mapping, $row): ?string {
                    $col = trim((string) ($mapping[$field] ?? ''));
                    if ($col === '') return null;
                    $v = $row[$col] ?? null;
                    if ($v === null) return null;
                    $s = is_string($v) ? $v : (is_numeric($v) ? (string) $v : (string) $v);
                    $s = trim($s);
                    return $s === '' ? null : $s;
                };

                $designation = $get('designation');
                $description = $get('description');
                $pv1Raw = $get('pv_1');
                $pv2Raw = $get('pv_2');
                $pv3Raw = $get('pv_3');
                $stockRaw = $get('stock');
                $catName = $get('categorie');
                $subCatName = $get('sous_categorie');
                $marqueName = $get('marque');

                $pv1 = $pv1Raw !== null ? (float) str_replace(',', '.', $pv1Raw) : null;
                $pv2 = $pv2Raw !== null ? (float) str_replace(',', '.', $pv2Raw) : null;
                $pv3 = $pv3Raw !== null ? (float) str_replace(',', '.', $pv3Raw) : null;
                $stockVal = $stockRaw !== null ? (int) round((float) str_replace(',', '.', $stockRaw)) : null;

                // Handle Category
                $catName = $catName !== null ? mb_substr($catName, 0, 100) : __('Général');
                if (! array_key_exists($catName, $catCache)) {
                    $slug = Str::slug($catName);
                    if ($slug === '') $slug = Str::slug('categorie-'.$catName);
                    try {
                        $catId = DB::table('categories')->insertGetId([
                            'id_frs' => $frsId,
                            'nom' => $catName,
                            'slug' => $slug,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $catCache[$catName] = $catId;
                    } catch (Throwable $e) {
                        $catCache[$catName] = DB::table('categories')->where('id_frs', $frsId)->where('nom', $catName)->value('id') ?? 0;
                    }
                }
                $catId = (int) ($catCache[$catName] ?? 0);

                // Handle Sub-category
                $subCatId = null;
                if ($subCatName !== null && $catId > 0) {
                    $subCatName = mb_substr($subCatName, 0, 100);
                    if (! isset($subCatCache[$catId])) $subCatCache[$catId] = [];
                    if (! array_key_exists($subCatName, $subCatCache[$catId])) {
                        try {
                            $scId = DB::table('sous_categories')->insertGetId([
                                'id_frs' => $frsId,
                                'id_categorie' => $catId,
                                'nom' => $subCatName,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $subCatCache[$catId][$subCatName] = $scId;
                        } catch (Throwable $e) {
                            $subCatCache[$catId][$subCatName] = DB::table('sous_categories')->where('id_frs', $frsId)->where('id_categorie', $catId)->where('nom', $subCatName)->value('id') ?? 0;
                        }
                    }
                    $subCatId = (int) ($subCatCache[$catId][$subCatName] ?? null);
                    if ($subCatId === 0) $subCatId = null;
                }

                // Handle Marque
                $marqueName = $marqueName !== null ? mb_substr($marqueName, 0, 100) : 'Standard';
                if (! array_key_exists($marqueName, $marqueCache)) {
                    try {
                        $mId = DB::table('marques')->insertGetId([
                            'id_frs' => $frsId,
                            'nom' => $marqueName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $marqueCache[$marqueName] = $mId;
                    } catch (Throwable $e) {
                        $marqueCache[$marqueName] = DB::table('marques')->where('id_frs', $frsId)->where('nom', $marqueName)->value('id') ?? 0;
                    }
                }
                $marqueId = (int) ($marqueCache[$marqueName] ?? 0);
                if ($marqueId === 0) $marqueId = null;

                $prod = $existing->get($reference);
                if ($prod) {
                    if (! $isAdmin) {
                        $skipped++;
                        continue;
                    }

                    $payload = [];
                    if (in_array('designation', $update, true) && $designation !== null) {
                        $payload['designation'] = mb_substr($designation, 0, 255);
                    }
                    if (in_array('description', $update, true) && $description !== null) {
                        $payload['description'] = $description;
                    }
                    if (in_array('pv_1', $update, true) && $pv1 !== null && $pv1 >= 0) {
                        $payload['pv_1'] = $pv1;
                    }
                    if (in_array('pv_2', $update, true) && $pv2 !== null && $pv2 >= 0) {
                        $payload['pv_2'] = $pv2;
                    }
                    if (in_array('pv_3', $update, true) && $pv3 !== null && $pv3 >= 0) {
                        $payload['pv_3'] = $pv3;
                    }
                    if (in_array('categorie', $update, true) && $catName !== null) {
                        $payload['categorie'] = $catName;
                        $payload['id_categorie'] = $catId > 0 ? $catId : null;
                    }
                    if (in_array('sous_categorie', $update, true) && $subCatName !== null) {
                        $payload['id_sous_categorie'] = $subCatId;
                    }
                    if (in_array('marque', $update, true) && $marqueName !== null) {
                        $payload['id_marque'] = $marqueId;
                    }
                    if (in_array('stock', $update, true) && $stockVal !== null && $stockVal >= 0) {
                        $payload['stock'] = $stockMode === 'add'
                            ? ((int) ($prod->stock ?? 0) + $stockVal)
                            : $stockVal;
                    }

                    if (count($payload) > 0) {
                        Produit::query()->where('id', $prod->id)->update($payload);
                        $updatedCount++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $new = [
                        'id_frs' => $frsId,
                        'reference' => $reference,
                        'designation' => mb_substr((string)($designation ?? $reference), 0, 255),
                        'description' => (string)($description ?? ''),
                        'pv_1' => (float) max(0, (float)($pv1 ?? 0)),
                        'pv_2' => (float) max(0, (float)($pv2 ?? ($pv1 ?? 0))),
                        'pv_3' => (float) max(0, (float)($pv3 ?? ($pv1 ?? 0))),
                        'stock' => (int) max(0, (int)($stockVal ?? 0)),
                        'categorie' => (string) ($catName ?? __('Général')),
                        'id_categorie' => $catId > 0 ? $catId : null,
                        'id_sous_categorie' => $subCatId,
                        'id_marque' => $marqueId,
                        'abonne_only' => 0,
                        'enable_tier_pricing' => 0,
                        'actif' => 1,
                    ];

                    if (trim($new['description']) === '') {
                        $new['description'] = '—';
                    }

                    try {
                        Produit::create($new);
                        $created++;
                    } catch (Throwable $e) {
                        $errors[] = ['row' => $i + 2, 'reference' => $reference, 'message' => __('Impossible de créer le produit.')];
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => __('Import terminé.'),
            'created' => $created,
            'updated' => $updatedCount,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    public function toggleActif(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $produit = Produit::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $produit->actif = (int) $produit->actif === 1 ? 0 : 1;
        $produit->save();

        return back()->with('success', __('Statut produit mis à jour.'));
    }
}
