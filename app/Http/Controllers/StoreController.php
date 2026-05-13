<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Cmd1;
use App\Models\Cmd2;
use App\Models\Commune;
use App\Models\Categorie;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Models\Wilaya;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    private function singleFournisseur(): ?Fournisseur
    {
        return Fournisseur::single();
    }

    private function canShowPrices(?Client $client, ?Fournisseur $frs): bool
    {
        if ($client) {
            return true;
        }

        return $frs && (int) ($frs->show_prices_to_guests ?? 1) === 1;
    }

    private function fraisLivraisonEnabled(?Fournisseur $frs): bool
    {
        return $frs && (int) ($frs->enable_frais_livraison ?? 0) === 1;
    }

    private function fraisLivraisonMap(int $frsId): array
    {
        return DB::table('frais_livraison')
            ->where('id_frs', $frsId)
            ->pluck('frais', 'id_wilaya')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function fraisLivraisonFor(int $frsId, int $idWilaya): float
    {
        $v = DB::table('frais_livraison')
            ->where('id_frs', $frsId)
            ->where('id_wilaya', $idWilaya)
            ->value('frais');

        return $v === null ? 0.0 : (float) $v;
    }

    private function currentClient(): ?Client
    {
        if (session('role') !== 'client' || ! session()->has('client_id')) {
            return null;
        }

        return Client::query()->find((int) session('client_id'));
    }

    private function tarifForClient(?Client $client): int
    {
        if (! $client || (string) $client->type_client !== 'abonne') {
            return 1;
        }

        $t = (int) ($client->tarif ?? 1);
        if ($t < 1 || $t > 3) {
            $t = 1;
        }

        return $t;
    }

    private function resolveUrl(?string $raw): string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return '';
        }

        $lower = strtolower($v);
        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
            return $v;
        }

        if (str_starts_with($v, '//')) {
            return request()->getScheme().':'.$v;
        }

        if (str_starts_with($v, '/')) {
            return url($v);
        }

        return url('/'.$v);
    }

    private function cart(): array
    {
        $cart = session('cart', []);
        return is_array($cart) ? $cart : [];
    }

    private function cartFournisseurId(): ?int
    {
        $id = session('cart_frs_id');
        if ($id === null || $id === '') {
            return null;
        }
        return (int) $id;
    }

    private function setCart(array $cart, ?int $frsId): void
    {
        session(['cart' => $cart]);
        if ($frsId) {
            session(['cart_frs_id' => $frsId]);
        } else {
            session()->forget('cart_frs_id');
        }
    }

    private function cartSummary(): array
    {
        $client = $this->currentClient();
        $singleFrsId = (int) ($this->singleFournisseur()?->id ?? 0);
        $canSeeInvisibleFournisseurId = ($client && (string) $client->type_client === 'abonne' && $client->id_frs)
            ? (int) $client->id_frs
            : null;
        $cart = $this->cart();
        $ids = array_keys($cart);
        $ids = array_map('intval', $ids);
        $ids = array_values(array_filter($ids, fn ($v) => $v > 0));

        if (count($ids) === 0) {
            return ['items' => [], 'total' => 0.0, 'frs' => null];
        }

        $products = Produit::query()
            ->whereNull('deleted_at')
            ->where('actif', 1)
            ->when(! $client || (string) $client->type_client !== 'abonne', fn ($q) => $q->where('abonne_only', 0))
            ->when($singleFrsId > 0, fn ($q) => $q->where('id_frs', $singleFrsId))
            ->whereIn('id', $ids)
            ->with(['fournisseur:id,nom_frs,actif,is_visible,deleted_at', 'quantityPrices'])
            ->get()
            ->keyBy('id');

        $items = [];
        $total = 0.0;

        foreach ($ids as $id) {
            $p = $products->get($id);
            if (! $p || ! $p->fournisseur || (int) $p->fournisseur->actif !== 1 || $p->fournisseur->deleted_at) {
                unset($cart[$id]);
                continue;
            }

            $qty = (int) ($cart[$id] ?? 0);
            if ($qty <= 0) {
                unset($cart[$id]);
                continue;
            }

            $qty = min($qty, (int) $p->stock);
            if ($qty <= 0) {
                unset($cart[$id]);
                continue;
            }

            $cart[$id] = $qty;

            $prixUnitaire = (float) $p->prixUnitairePourQuantite($client, $qty);
            $line = $prixUnitaire * $qty;
            $total += $line;

            $items[] = [
                'produit' => $p,
                'qty' => $qty,
                'line_total' => $line,
                'prix_unitaire' => $prixUnitaire,
                'image' => $this->resolveUrl($p->image_principale),
            ];
        }

        $frs = null;
        $frsId = $this->cartFournisseurId();
        if ($singleFrsId > 0) {
            $frsId = $singleFrsId;
        }
        if ($frsId) {
            $frs = Fournisseur::query()
                ->where('id', $frsId)
                ->where('actif', 1)
                ->whereNull('deleted_at')
                ->first([
                    'id',
                    'nom_frs',
                    'logo_path',
                    'adresse',
                    'telephone',
                    'id_wilaya',
                    'id_commune',
                    'latitude',
                    'longitude',
                    'show_prices_to_guests',
                    'enable_frais_livraison',
                    'meta_pixel_id',
                    'tiktok_pixel_id',
                ]);
        }

        $this->setCart($cart, $frsId);

        return ['items' => $items, 'total' => $total, 'frs' => $frs];
    }

    public function index(Request $request): View
    {
        $client = $this->currentClient();
        $boutique = $this->singleFournisseur();
        $canShowPrices = $this->canShowPrices($client, $boutique);

        $q = trim((string) $request->query('q', ''));
        $categorie = trim((string) $request->query('categorie', ''));
        $fournisseurId = $boutique ? (int) $boutique->id : null;

        $produitsQuery = Produit::query()
            ->whereNull('deleted_at')
            ->where('actif', 1)
            ->when(! $client || (string) $client->type_client !== 'abonne', fn ($q) => $q->where('abonne_only', 0))
            ->with(['fournisseur:id,nom_frs,actif,is_visible,deleted_at', 'quantityPrices'])
            ->when($fournisseurId, fn ($q) => $q->where('id_frs', $fournisseurId), fn ($q) => $q->whereRaw('1=0'))
            ->when($categorie !== '', fn ($q2) => $q2->where('categorie', $categorie))
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where(function ($sub) use ($q) {
                    $sub->where('designation', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%")
                        ->orWhere('categorie', 'like', "%{$q}%");
                });
            });

        $catsQuery = $fournisseurId
            ? Categorie::query()
                ->where('id_frs', $fournisseurId)
                ->orderBy('nom')
                ->pluck('nom')
                ->values()
            : collect();

        $produits = $produitsQuery
            ->orderByDesc('created_at')
            ->paginate(18)
            ->withQueryString();

        $cartSummary = $this->cartSummary();

        return view('store.index', [
            'title' => $boutique?->nom_frs ? ($boutique->nom_frs.' - Produits') : 'Produits',
            'client' => $client,
            'boutique' => $boutique,
            'produits' => $produits,
            'categories' => $catsQuery,
            'selected_categorie' => $categorie,
            'q' => $q,
            'cart_total' => $cartSummary['total'],
            'cart_count' => count($cartSummary['items']),
            'can_show_prices' => $canShowPrices,
        ]);
    }

    public function boutique(int $id, Request $request): View
    {
        return $this->index($request);
    }

    public function produit(int $id): View
    {
        $client = $this->currentClient();
        $boutique = $this->singleFournisseur();
        $canShowPrices = $this->canShowPrices($client, $boutique);
        $fournisseurId = (int) ($boutique?->id ?? 0);

        $p = Produit::query()
            ->whereNull('deleted_at')
            ->where('actif', 1)
            ->with(['images' => fn ($q) => $q->orderBy('ordre'), 'fournisseur:id,nom_frs,actif,is_visible,deleted_at', 'quantityPrices'])
            ->when($fournisseurId > 0, fn ($q) => $q->where('id_frs', $fournisseurId))
            ->findOrFail($id);

        if (! $client || (string) $client->type_client !== 'abonne') {
            if ((int) ($p->abonne_only ?? 0) === 1) {
                abort(404);
            }
        }

        $images = [];
        $main = $this->resolveUrl($p->image_principale);
        if ($main !== '') {
            $images[] = $main;
        }
        foreach ($p->images as $img) {
            $u = $this->resolveUrl($img->url_principale);
            if ($u !== '') {
                $images[] = $u;
            }
        }
        $images = array_values(array_unique($images));

        $cartSummary = $this->cartSummary();

        $tierModels = $p->relationLoaded('quantityPrices')
            ? $p->quantityPrices
            : $p->quantityPrices()->get(['quantity_min', 'quantity_max', 'price']);

        $tiers = $tierModels
            ->map(fn ($t) => [
                'quantity_min' => (int) $t->quantity_min,
                'quantity_max' => $t->quantity_max === null ? null : (int) $t->quantity_max,
                'price' => (float) $t->price,
            ])
            ->values()
            ->all();

        $tierEnabled = $p->isTierPricingEnabled() && count($tiers) > 0;
        $initialQty = 1;
        $initialUnit = (float) $p->prixUnitairePourQuantite($client ?? null, $initialQty);

        return view('store.produit', [
            'title' => $p->designation,
            'client' => $client,
            'boutique' => $boutique,
            'produit' => $p,
            'images' => $images,
            'tiers' => $tiers,
            'tierEnabled' => $tierEnabled,
            'initialQty' => $initialQty,
            'initialUnit' => $initialUnit,
            'cart_total' => $cartSummary['total'],
            'cart_count' => count($cartSummary['items']),
            'can_show_prices' => $canShowPrices,
        ]);
    }

    public function panier(): View
    {
        $client = $this->currentClient();
        $summary = $this->cartSummary();
        $boutique = $summary['frs'] ?? $this->singleFournisseur();
        $canShowPrices = $this->canShowPrices($client, $boutique);

        return view('store.panier', [
            'title' => 'Panier',
            'client' => $client,
            'items' => $summary['items'],
            'total' => $summary['total'],
            'boutique' => $boutique,
            'can_show_prices' => $canShowPrices,
        ]);
    }

    public function panierAdd(Request $request): RedirectResponse
    {
        $client = $this->currentClient();
        $singleFrsId = (int) ($this->singleFournisseur()?->id ?? 0);
        $data = $request->validate([
            'produit_id' => ['required', 'integer', 'min:1'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $qty = isset($data['qty']) ? (int) $data['qty'] : 1;

        $p = Produit::query()
            ->whereNull('deleted_at')
            ->where('actif', 1)
            ->when($singleFrsId > 0, fn ($q) => $q->where('id_frs', $singleFrsId))
            ->with(['fournisseur:id,actif,deleted_at'])
            ->findOrFail((int) $data['produit_id']);

        if (! $p->fournisseur || (int) $p->fournisseur->actif !== 1 || $p->fournisseur->deleted_at) {
            return back()->with('error', 'Produit indisponible.');
        }

        if (! $client || (string) $client->type_client !== 'abonne') {
            if ((int) ($p->abonne_only ?? 0) === 1) {
                return back()->with('error', 'Produit réservé aux abonnés.');
            }
        }

        if ((int) $p->stock <= 0) {
            return back()->with('error', 'Produit en rupture de stock.');
        }

        $cart = $this->cart();
        $newFrsId = (int) ($singleFrsId > 0 ? $singleFrsId : $p->id_frs);

        $existing = (int) ($cart[$p->id] ?? 0);
        $next = min($existing + $qty, (int) $p->stock);
        $cart[$p->id] = $next;
        $this->setCart($cart, $newFrsId);

        return back()->with('success', 'Ajouté au panier.');
    }

    public function panierUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'produit_id' => ['required', 'integer', 'min:1'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $singleFrsId = (int) ($this->singleFournisseur()?->id ?? 0);
        $cart = $this->cart();
        $id = (int) $data['produit_id'];

        if (! array_key_exists($id, $cart)) {
            return back();
        }

        $p = Produit::query()
            ->whereNull('deleted_at')
            ->where('actif', 1)
            ->when($singleFrsId > 0, fn ($q) => $q->where('id_frs', $singleFrsId))
            ->find($id);

        if (! $p) {
            unset($cart[$id]);
            $this->setCart($cart, $this->cartFournisseurId());
            return back();
        }

        $qty = min((int) $data['qty'], (int) $p->stock);
        if ($qty <= 0) {
            unset($cart[$id]);
        } else {
            $cart[$id] = $qty;
        }

        $frsId = $this->cartFournisseurId();
        if (count($cart) === 0) {
            $frsId = null;
        }
        $this->setCart($cart, $frsId);

        return back();
    }

    public function panierRemove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'produit_id' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->cart();
        $id = (int) $data['produit_id'];
        unset($cart[$id]);

        $frsId = $this->cartFournisseurId();
        if (count($cart) === 0) {
            $frsId = null;
        }
        $this->setCart($cart, $frsId);

        return back();
    }

    public function panierClear(): RedirectResponse
    {
        session()->forget(['cart', 'cart_frs_id']);
        return back();
    }

    public function checkout(): RedirectResponse|View
    {
        $client = $this->currentClient();
        if (! $client) {
            session(['url.intended' => url('/checkout')]);
            return redirect()->to('/login')->with('error', 'Connectez-vous pour continuer.');
        }

        $summary = $this->cartSummary();
        if (count($summary['items']) === 0) {
            return redirect()->to('/panier')->with('error', 'Votre panier est vide.');
        }

        $boutique = $this->singleFournisseur();

        $wilayas = Wilaya::query()->orderBy('ID_WILAYA')->get(['ID_WILAYA', 'WILAYA']);
        $selectedWilaya = (int) ($client->id_wilaya ?? 0);
        if ($selectedWilaya <= 0) {
            $selectedWilaya = (int) ($wilayas->first()?->ID_WILAYA ?? 1);
        }

        $communes = Commune::query()
            ->where('ID_WILAYA', $selectedWilaya)
            ->orderBy('COMMUNE')
            ->get(['ID_COMMUNE', 'COMMUNE', 'ID_WILAYA']);

        $shippingEnabled = $this->fraisLivraisonEnabled($boutique);
        $feesMap = ($shippingEnabled && $boutique) ? $this->fraisLivraisonMap((int) $boutique->id) : [];
        $shippingFee = ($shippingEnabled && $boutique) ? ($feesMap[$selectedWilaya] ?? 0.0) : 0.0;

        return view('store.checkout', [
            'title' => 'Finaliser la commande',
            'client' => $client,
            'items' => $summary['items'],
            'total' => $summary['total'],
            'boutique' => $boutique,
            'wilayas' => $wilayas,
            'communes' => $communes,
            'selected_wilaya' => $selectedWilaya,
            'shipping_enabled' => $shippingEnabled,
            'shipping_fees' => $feesMap,
            'shipping_fee' => $shippingFee,
            'total_with_shipping' => (float) $summary['total'] + $shippingFee,
        ]);
    }

    public function checkoutStore(Request $request): RedirectResponse
    {
        $client = $this->currentClient();
        if (! $client) {
            session(['url.intended' => url('/checkout')]);
            return redirect()->to('/login')->with('error', 'Connectez-vous pour continuer.');
        }

        $data = $request->validate([
            'adresse_livraison' => ['required', 'string', 'max:255'],
            'id_wilaya' => ['required', 'integer', 'exists:wilaya,ID_WILAYA'],
            'id_commune' => ['required', 'integer', 'exists:commune,ID_COMMUNE'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $summary = $this->cartSummary();
        if (count($summary['items']) === 0) {
            return redirect()->to('/panier')->with('error', 'Votre panier est vide.');
        }

        $frsId = (int) ($this->singleFournisseur()?->id ?? 0);
        if ($frsId <= 0) {
            return redirect()->to('/panier')->with('error', 'Boutique introuvable.');
        }

        $result = DB::transaction(function () use ($client, $summary, $frsId, $data) {
            $frs = Fournisseur::query()
                ->where('id', $frsId)
                ->where('actif', 1)
                ->whereNull('deleted_at')
                ->first();

            if (! $frs) {
                throw new \RuntimeException('Fournisseur introuvable.');
            }

            $sousTotal = 0.0;
            $lines = [];

            foreach ($summary['items'] as $it) {
                /** @var Produit $p */
                $p = $it['produit'];
                $qty = (int) $it['qty'];

                $pdb = Produit::query()
                    ->where('id', $p->id)
                    ->where('id_frs', $frs->id)
                    ->whereNull('deleted_at')
                    ->where('actif', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $pdb) {
                    throw new \RuntimeException("Produit {$p->id} introuvable.");
                }

                if ((string) $client->type_client !== 'abonne' && (int) ($pdb->abonne_only ?? 0) === 1) {
                    throw new \RuntimeException("Produit {$pdb->id} réservé aux abonnés.");
                }

                if ((int) $pdb->stock < $qty) {
                    throw new \RuntimeException("Stock insuffisant pour {$pdb->designation}.");
                }

                $prixUnitaire = (float) $pdb->prixUnitairePourQuantite($client, $qty);
                $lineTotal = $prixUnitaire * $qty;
                $sousTotal += $lineTotal;

                $lines[] = [
                    'id_produit' => (int) $pdb->id,
                    'quantite' => $qty,
                    'prix_unitaire' => $prixUnitaire,
                    'sous_total' => $lineTotal,
                ];

                $pdb->update(['stock' => (int) $pdb->stock - $qty]);
            }

            $fraisLivraison = 0.0;
            if ((int) ($frs->enable_frais_livraison ?? 0) === 1) {
                $fraisLivraison = (float) DB::table('frais_livraison')
                    ->where('id_frs', (int) $frs->id)
                    ->where('id_wilaya', (int) $data['id_wilaya'])
                    ->value('frais');
                if ($fraisLivraison < 0) {
                    $fraisLivraison = 0.0;
                }
            }

            $montantTotal = $sousTotal + $fraisLivraison;

            $cmd = Cmd1::create([
                'id_client' => (int) $client->id,
                'id_frs' => (int) $frs->id,
                'date_cmd' => Carbon::now(),
                'statut' => 'en_attente',
                'montant_total' => $montantTotal,
                'sous_total' => $sousTotal,
                'frais_livraison' => $fraisLivraison,
                'adresse_livraison' => $data['adresse_livraison'],
                'id_wilaya' => (int) $data['id_wilaya'],
                'id_commune' => (int) $data['id_commune'],
                'notes' => $data['notes'] ?? null,
                'synced_pme' => 0,
            ]);

            foreach ($lines as $l) {
                Cmd2::create([
                    'id_cmd' => (int) $cmd->id,
                    'id_produit' => (int) $l['id_produit'],
                    'quantite' => (int) $l['quantite'],
                    'prix_unitaire' => (float) $l['prix_unitaire'],
                    'sous_total' => (float) $l['sous_total'],
                ]);
            }

            return $cmd;
        });

        $contents = collect($summary['items'])->map(function ($it) {
            $p = $it['produit'];
            return ['id' => (string) ($p->id ?? ''), 'quantity' => (int) ($it['qty'] ?? 1)];
        })->filter(fn ($r) => ($r['id'] ?? '') !== '')->values()->all();

        session()->flash('pixel_purchase', [
            'order_id' => (int) $result->id,
            'value' => (float) ($result->montant_total ?? 0),
            'currency' => 'DZD',
            'contents' => $contents,
        ]);

        session()->forget(['cart', 'cart_frs_id']);

        return redirect()->to('/mes-commandes/'.$result->id)->with('success', 'Commande créée.');
    }

    public function mesCommandes(): RedirectResponse|View
    {
        $client = $this->currentClient();
        if (! $client) {
            session(['url.intended' => url('/mes-commandes')]);
            return redirect()->to('/login')->with('error', 'Connectez-vous pour continuer.');
        }

        $commandes = Cmd1::query()
            ->leftJoin('frs', 'frs.id', '=', 'cmd1.id_frs')
            ->select(['cmd1.*', 'frs.nom_frs as frs_nom'])
            ->where('cmd1.id_client', $client->id)
            ->orderByDesc('cmd1.date_cmd')
            ->paginate(15);

        return view('store.commandes.index', [
            'title' => 'Mes commandes',
            'client' => $client,
            'commandes' => $commandes,
        ]);
    }

    public function commandeShow(int $id): RedirectResponse|View
    {
        $client = $this->currentClient();
        if (! $client) {
            session(['url.intended' => url('/mes-commandes/'.$id)]);
            return redirect()->to('/login')->with('error', 'Connectez-vous pour continuer.');
        }

        $commande = Cmd1::query()
            ->leftJoin('frs', 'frs.id', '=', 'cmd1.id_frs')
            ->leftJoin('wilaya', 'wilaya.ID_WILAYA', '=', 'cmd1.id_wilaya')
            ->select(['cmd1.*', 'frs.nom_frs as frs_nom', 'wilaya.WILAYA as wilaya_nom'])
            ->where('cmd1.id_client', $client->id)
            ->where('cmd1.id', $id)
            ->firstOrFail();

        $lignes = Cmd2::query()
            ->leftJoin('produit', 'produit.id', '=', 'cmd2.id_produit')
            ->select([
                'cmd2.*',
                'produit.designation as produit_designation',
                'produit.reference as produit_reference',
                'produit.image_principale as produit_image',
            ])
            ->where('cmd2.id_cmd', $commande->id)
            ->orderBy('cmd2.id')
            ->get()
            ->map(function ($l) {
                $l->produit_image_url = $this->resolveUrl($l->produit_image ?? '');
                return $l;
            });

        return view('store.commandes.show', [
            'title' => 'Commande #'.$commande->id,
            'client' => $client,
            'commande' => $commande,
            'lignes' => $lignes,
        ]);
    }
}
