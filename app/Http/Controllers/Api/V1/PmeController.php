<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PmeSyncClientsRequest;
use App\Http\Requests\Api\V1\PmeSyncProduitsRequest;
use App\Models\Categorie;
use App\Models\Client;
use App\Models\Cmd1;
use App\Models\Cmd2;
use App\Models\Marque;
use App\Models\Produit;
use App\Models\SousCategorie;
use App\Notifications\StatutCommandeChange;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PmeController extends Controller
{
    use ApiResponseTrait;

    public function exportCommandesCsv(Request $request)
    {
        $frs = $request->attributes->get('fournisseur');
        $synced = $request->query('synced', '0');
        $syncedValue = $synced === '1' ? 1 : 0;

        $filename = 'commandes_frs_'.$frs->id.'_synced_'.$syncedValue.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($frs, $syncedValue) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'commande_id',
                'client_id',
                'date_cmd',
                'statut',
                'montant_total',
                'adresse_livraison',
                'id_wilaya',
                'id_commune',
                'notes',
                'synced_pme',
                'produit_id',
                'reference',
                'designation',
                'quantite',
                'prix_unitaire',
                'sous_total',
            ], ';');

            $rows = Cmd1::query()
                ->leftJoin('cmd2', 'cmd2.id_cmd', '=', 'cmd1.id')
                ->leftJoin('produit', 'produit.id', '=', 'cmd2.id_produit')
                ->select([
                    'cmd1.id as commande_id',
                    'cmd1.id_client',
                    'cmd1.date_cmd',
                    'cmd1.statut',
                    'cmd1.montant_total',
                    'cmd1.adresse_livraison',
                    'cmd1.id_wilaya',
                    'cmd1.id_commune',
                    'cmd1.notes',
                    'cmd1.synced_pme',
                    'cmd2.id_produit',
                    'cmd2.quantite',
                    'cmd2.prix_unitaire',
                    'cmd2.sous_total',
                    'produit.reference as produit_reference',
                    'produit.designation as produit_designation',
                ])
                ->where('cmd1.id_frs', $frs->id)
                ->where('cmd1.synced_pme', $syncedValue)
                ->orderByDesc('cmd1.date_cmd')
                ->orderByDesc('cmd1.id')
                ->cursor();

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->commande_id,
                    $r->id_client,
                    (string) $r->date_cmd,
                    (string) $r->statut,
                    (string) $r->montant_total,
                    (string) $r->adresse_livraison,
                    $r->id_wilaya,
                    $r->id_commune,
                    (string) ($r->notes ?? ''),
                    $r->synced_pme,
                    $r->id_produit,
                    (string) ($r->produit_reference ?? ''),
                    (string) ($r->produit_designation ?? ''),
                    $r->quantite,
                    (string) ($r->prix_unitaire ?? ''),
                    (string) ($r->sous_total ?? ''),
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function syncClients(PmeSyncClientsRequest $request)
    {
        $frs = $request->attributes->get('fournisseur');
        $payload = $request->validated()['clients'];

        $inserted = 0;
        $updated = 0;
        $failed = [];

        foreach ($payload as $item) {
            try {
                $hashed = $item['password'];
                if (! str_starts_with($hashed, '$')) {
                    $hashed = Hash::make($hashed);
                }

                $existing = Client::query()
                    ->where('id_frs', $frs->id)
                    ->where('code_client', $item['code_client'])
                    ->first();

                $data = [
                    'code_client' => $item['code_client'],
                    'nom' => $item['nom'],
                    'prenom' => $item['prenom'],
                    'email' => $item['email'],
                    'password' => $hashed,
                    'telephone' => $item['telephone'] ?? null,
                    'adresse' => '',
                    'id_wilaya' => (int) $item['id_wilaya'],
                    'id_commune' => (int) $item['id_commune'],
                    'type_client' => 'abonne',
                    'tarif' => (int) ($item['tarif'] ?? 1),
                    'id_frs' => $frs->id,
                    'actif' => 1,
                    'email_verified_at' => now(),
                ];

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    Client::create($data);
                    $inserted++;
                }
            } catch (\Throwable $e) {
                $failed[] = [
                    'code_client' => $item['code_client'],
                    'email' => $item['email'],
                    'error' => 'Erreur lors de la synchronisation',
                ];
            }
        }

        return $this->success([
            'nb_inseres' => $inserted,
            'nb_mis_a_jour' => $updated,
            'nb_erreurs' => count($failed),
            'erreurs' => $failed,
        ], 'Sync clients terminé');
    }

    public function syncProduits(PmeSyncProduitsRequest $request)
    {
        $frs = $request->attributes->get('fournisseur');
        $items = $request->validated()['produits'];

        $inserted = 0;
        $updated = 0;
        $categoryCache = [];
        $subCategoryCache = [];
        $brandCache = [];

        DB::transaction(function () use ($frs, $items, &$inserted, &$updated, &$categoryCache, &$subCategoryCache, &$brandCache) {
            foreach ($items as $item) {
                $categoryName = $this->normalizeCatalogName((string) ($item['categorie'] ?? ''));
                $subCategoryName = $this->normalizeCatalogName((string) ($item['sous_categorie'] ?? ''));
                $brandName = $this->normalizeCatalogName((string) ($item['marque'] ?? ''));

                $categoryId = $this->resolveCategoryId($frs->id, $categoryName, $categoryCache);
                $subCategoryId = $this->resolveSubCategoryId($frs->id, $categoryId, $subCategoryName, $subCategoryCache);
                $brandId = $this->resolveBrandId($frs->id, $brandName, $brandCache);

                $existing = Produit::withTrashed()
                    ->where('id_frs', $frs->id)
                    ->where('reference', $item['reference'])
                    ->first();

                $data = [
                    'id_frs' => $frs->id,
                    'reference' => $item['reference'],
                    'designation' => $item['designation'],
                    'description' => $existing?->description ?? '',
                    'pv_1' => $item['pv_1'] ?? ($item['prix'] ?? 0),
                    'pv_2' => $item['pv_2'] ?? ($item['pv_1'] ?? ($item['prix'] ?? 0)),
                    'pv_3' => $item['pv_3'] ?? ($item['pv_1'] ?? ($item['prix'] ?? 0)),
                    'tva' => array_key_exists('tva', $item) && $item['tva'] !== null && $item['tva'] !== ''
                        ? (float) $item['tva']
                        : null,
                    'promo_enabled' => (int) ($item['promo'] ?? 0) === 1 ? 1 : 0,
                    'promo_start_at' => $item['date_debut_promo'] ?? null,
                    'promo_end_at' => $item['date_fin_promo'] ?? null,
                    'promo_quantity' => ! empty($item['quantite_promo']) ? (int) $item['quantite_promo'] : null,
                    'promo_price' => array_key_exists('prix_promo', $item) && $item['prix_promo'] !== null && $item['prix_promo'] !== ''
                        ? (float) $item['prix_promo']
                        : null,
                    'stock' => (int) round((float) $item['stock']),
                    'categorie' => $categoryName,
                    'id_categorie' => $categoryId,
                    'id_sous_categorie' => $subCategoryId,
                    'id_marque' => $brandId,
                    'abonne_only' => (int) ($item['abonne_only'] ?? 0) === 1 ? 1 : 0,
                    'actif' => 1,
                ];

                if ($existing) {
                    if (method_exists($existing, 'trashed') && $existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update($data);
                    $updated++;
                } else {
                    Produit::create($data);
                    $inserted++;
                }
            }
        });

        return $this->success([
            'nb_inseres' => $inserted,
            'nb_mis_a_jour' => $updated,
        ], 'Sync produits terminé');
    }

    private function normalizeCatalogName(string $value): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $normalized;
    }

    private function resolveCategoryId(int $frsId, string $name, array &$cache): ?int
    {
        if ($name === '') {
            return null;
        }

        $key = Str::lower($name);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $category = Categorie::query()
            ->where('id_frs', $frsId)
            ->whereRaw('LOWER(nom) = ?', [$key])
            ->first();

        if (! $category) {
            $baseSlug = Str::slug($name);
            if ($baseSlug === '') {
                $baseSlug = 'categorie-'.Str::lower(Str::random(8));
            }

            $slug = $baseSlug;
            $suffix = 2;
            while (Categorie::query()->where('id_frs', $frsId)->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $category = Categorie::create([
                'id_frs' => $frsId,
                'nom' => $name,
                'slug' => $slug,
            ]);
        }

        $cache[$key] = (int) $category->id;

        return $cache[$key];
    }

    private function resolveSubCategoryId(int $frsId, ?int $categoryId, string $name, array &$cache): ?int
    {
        if ($categoryId === null || $name === '') {
            return null;
        }

        $key = $categoryId.'|'.Str::lower($name);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $subCategory = SousCategorie::query()
            ->where('id_frs', $frsId)
            ->where('id_categorie', $categoryId)
            ->whereRaw('LOWER(nom) = ?', [Str::lower($name)])
            ->first();

        if (! $subCategory) {
            $subCategory = SousCategorie::create([
                'id_frs' => $frsId,
                'id_categorie' => $categoryId,
                'nom' => $name,
            ]);
        }

        $cache[$key] = (int) $subCategory->id;

        return $cache[$key];
    }

    private function resolveBrandId(int $frsId, string $name, array &$cache): ?int
    {
        $brandName = $name !== '' ? mb_substr($name, 0, 100) : 'Standard';
        $key = Str::lower($brandName);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $brandId = Marque::query()
            ->where('id_frs', $frsId)
            ->whereRaw('LOWER(nom) = ?', [$key])
            ->value('id');

        if (! $brandId) {
            try {
                $brandId = DB::table('marques')->insertGetId([
                    'id_frs' => $frsId,
                    'nom' => $brandName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (Throwable $e) {
                $brandId = DB::table('marques')
                    ->where('id_frs', $frsId)
                    ->where('nom', $brandName)
                    ->value('id');
            }
        }

        $cache[$key] = $brandId ? (int) $brandId : 0;

        return $cache[$key] > 0 ? $cache[$key] : null;
    }

    public function syncFournisseur(Request $request)
    {
        $frs = $request->attributes->get('fournisseur');

        $data = $request->validate([
            'nom_frs' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'id_wilaya' => ['nullable', 'integer', 'exists:wilaya,ID_WILAYA'],
            'id_commune' => ['nullable', 'integer', 'exists:commune,ID_COMMUNE'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $payload = [];
        foreach (['nom_frs', 'telephone', 'adresse', 'id_wilaya', 'id_commune', 'latitude', 'longitude', 'is_visible'] as $key) {
            if (array_key_exists($key, $data)) {
                if ($key === 'is_visible') {
                    $payload[$key] = (int) $data[$key] === 1 ? 1 : 0;
                } else {
                    $payload[$key] = $data[$key];
                }
            }
        }

        if ((int) ($data['remove_logo'] ?? 0) === 1) {
            if (! empty($frs->logo_path)) {
                Storage::disk('public')->delete($frs->logo_path);
            }
            $payload['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if (! empty($frs->logo_path)) {
                Storage::disk('public')->delete($frs->logo_path);
            }
            $ext = strtolower((string) $request->file('logo')->getClientOriginalExtension());
            if ($ext === '') {
                $ext = 'jpg';
            }
            $path = $request->file('logo')->storeAs(
                "frs/{$frs->id}",
                'logo_'.now()->timestamp.'.'.$ext,
                'public'
            );
            $payload['logo_path'] = $path;
        }

        if (count($payload) > 0) {
            $frs->update($payload);
        }

        $frs->refresh();

        return $this->success([
            'id' => (int) $frs->id,
            'nom_frs' => $frs->nom_frs,
            'telephone' => $frs->telephone,
            'adresse' => $frs->adresse,
            'id_wilaya' => (int) $frs->id_wilaya,
            'id_commune' => (int) $frs->id_commune,
            'latitude' => $frs->latitude,
            'longitude' => $frs->longitude,
            'logo_url' => $frs->logo_url,
            'is_visible' => (int) ($frs->is_visible ?? 1),
        ], 'Sync fournisseur terminé');
    }

    public function commandes(Request $request)
    {
        $frs = $request->attributes->get('fournisseur');
        $synced = $request->query('synced', '0');
        $syncedValue = $synced === '1' ? 1 : 0;

        $commandes = Cmd1::query()
            ->leftJoin('client', 'client.id', '=', 'cmd1.id_client')
            ->leftJoin('wilaya', 'wilaya.ID_WILAYA', '=', 'cmd1.id_wilaya')
            ->leftJoin('commune', 'commune.ID_COMMUNE', '=', 'cmd1.id_commune')
            ->select([
                'cmd1.*',
                'client.nom as client_nom',
                'client.prenom as client_prenom',
                'client.code_client as client_code_client',
                'client.telephone as client_telephone',
                'client.tarif as client_tarif',
                'wilaya.WILAYA as wilaya_nom',
                'commune.COMMUNE as commune_nom',
            ])
            ->where('cmd1.id_frs', $frs->id)
            ->where('cmd1.synced_pme', $syncedValue)
            ->orderByDesc('cmd1.date_cmd')
            ->limit(200)
            ->get();

        $ids = $commandes->pluck('id')->all();

        $lignes = [];
        if (count($ids) > 0) {
            $rows = Cmd2::query()
                ->leftJoin('produit', 'produit.id', '=', 'cmd2.id_produit')
                ->select([
                    'cmd2.id_cmd',
                    'cmd2.id_produit',
                    'cmd2.quantite',
                    'cmd2.prix_unitaire',
                    'cmd2.sous_total',
                    'produit.reference as produit_reference',
                    'produit.designation as produit_designation',
                ])
                ->whereIn('cmd2.id_cmd', $ids)
                ->orderBy('cmd2.id_cmd')
                ->orderBy('cmd2.id')
                ->get();

            foreach ($rows as $r) {
                $lignes[$r->id_cmd][] = [
                    'id_produit' => (int) $r->id_produit,
                    'reference' => $r->produit_reference,
                    'designation' => $r->produit_designation,
                    'quantite' => (int) $r->quantite,
                    'prix_unitaire' => (float) $r->prix_unitaire,
                    'sous_total' => (float) $r->sous_total,
                ];
            }
        }

        $items = $commandes->map(function ($c) use ($lignes) {
            $clientNom = trim(implode(' ', array_filter([
                $c->client_nom,
                $c->client_prenom,
            ])));

            return [
                'id' => (int) $c->id,
                'id_client' => (int) $c->id_client,
                'date_cmd' => (string) $c->date_cmd,
                'client_nom' => $clientNom !== '' ? $clientNom : 'Client #'.$c->id_client,
                'code_client' => (string) ($c->client_code_client ?? ''),
                'telephone_client' => (string) ($c->client_telephone ?? ''),
                'mode_tarif' => (string) ($c->client_tarif ?? '0'),
                'statut' => (string) $c->statut,
                'sous_total' => (float) ($c->sous_total ?? 0),
                'frais_livraison' => (float) ($c->frais_livraison ?? 0),
                'montant_total' => (float) $c->montant_total,
                'adresse_livraison' => $c->adresse_livraison,
                'id_wilaya' => (int) $c->id_wilaya,
                'id_commune' => (int) $c->id_commune,
                'wilaya_nom' => (string) ($c->wilaya_nom ?? ''),
                'commune_nom' => (string) ($c->commune_nom ?? ''),
                'notes' => $c->notes,
                'synced_pme' => (int) $c->synced_pme,
                'lignes' => $lignes[$c->id] ?? [],
            ];
        })->values();

        return $this->success($items, 'Commandes PME');
    }

    public function updateCommandeStatus(Request $request, int $id)
    {
        $frs = $request->attributes->get('fournisseur');

        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,validee,expediee,livree,annulee'],
        ]);

        /** @var Cmd1|null $cmd */
        $cmd = Cmd1::query()
            ->where('id', $id)
            ->where('id_frs', $frs->id)
            ->first();

        if (! $cmd) {
            return $this->notFound();
        }

        $newStatus = (string) $data['statut'];
        if ((string) $cmd->statut !== $newStatus) {
            $cmd->update(['statut' => $newStatus]);

            $client = Client::query()->find($cmd->id_client);
            if ($client) {
                $client->notify(new StatutCommandeChange($cmd, $newStatus));
            }
        }

        return $this->success([
            'id' => (int) $cmd->id,
            'statut' => (string) $cmd->statut,
            'synced_pme' => (int) $cmd->synced_pme,
        ], 'Statut commande mis a jour');
    }

    public function markSynced(Request $request, int $id)
    {
        $frs = $request->attributes->get('fournisseur');

        $cmd = Cmd1::query()
            ->where('id', $id)
            ->where('id_frs', $frs->id)
            ->first();

        if (! $cmd) {
            return $this->notFound();
        }

        $cmd->update(['synced_pme' => 1]);

        return $this->success([
            'id' => $cmd->id,
            'synced_pme' => 1,
        ], 'Commande marquée synchronisée');
    }
}
