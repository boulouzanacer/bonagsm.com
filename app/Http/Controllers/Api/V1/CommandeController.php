<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CommandeStoreRequest;
use App\Models\Cmd1;
use App\Models\Cmd2;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Notifications\NouvelleCommande;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    use ApiResponseTrait;

    public function store(CommandeStoreRequest $request)
    {
        $data = $request->validated();
        $client = $request->user();
        $frsId = (int) $data['id_frs'];

        if ((string) $client->type_client === 'abonne' && $client->id_frs && (int) $client->id_frs !== $frsId) {
            return $this->error('Non autorisé', null, 403);
        }

        $allowInvisible = (string) $client->type_client === 'abonne' && $client->id_frs && (int) $client->id_frs === $frsId;

        $frs = Fournisseur::query()
            ->where('id', $frsId)
            ->where('actif', 1)
            ->when(! $allowInvisible, fn ($q) => $q->where('is_visible', 1))
            ->whereNull('deleted_at')
            ->first();
        if (! $frs) {
            return $this->error('Fournisseur introuvable ou inactif', null, 404);
        }

        $panier = $data['panier'];

        try {
            $result = DB::transaction(function () use ($client, $frs, $data, $panier) {
                $montantTotal = 0.0;
                $lines = [];

                $produitsById = [];
                foreach ($panier as $item) {
                    $idProduit = (int) $item['id_produit'];
                    $produit = Produit::query()
                        ->where('id', $idProduit)
                        ->where('id_frs', $frs->id)
                        ->where('actif', 1)
                        ->whereNull('deleted_at')
                        ->lockForUpdate()
                        ->first();

                    if (! $produit) {
                        throw new \RuntimeException("Produit {$idProduit} introuvable");
                    }

                    if ((string) $client->type_client !== 'abonne' && (int) ($produit->abonne_only ?? 0) === 1) {
                        throw new \RuntimeException("Produit {$idProduit} réservé aux abonnés");
                    }

                    $qte = (int) $item['quantite'];
                    if ((int) $produit->stock < $qte) {
                        throw new \RuntimeException("Stock insuffisant pour le produit {$produit->id}");
                    }

                    $prix = (float) $produit->prixUnitairePourQuantite($client, $qte);
                    $sousTotal = $prix * $qte;
                    $montantTotal += $sousTotal;

                    $lines[] = [
                        'produit' => $produit,
                        'id_produit' => $produit->id,
                        'quantite' => $qte,
                        'prix_unitaire' => $prix,
                        'sous_total' => $sousTotal,
                    ];

                    $produitsById[$produit->id] = $produit;
                }

                $fraisLivraison = 0.0;
                if ((int) ($frs->enable_frais_livraison ?? 0) === 1) {
                    $f = DB::table('frais_livraison')
                        ->where('id_frs', (int) $frs->id)
                        ->where('id_wilaya', (int) $data['id_wilaya'])
                        ->value('frais');
                    if ($f !== null && (float) $f > 0) {
                        $fraisLivraison = (float) $f;
                    }
                }

                $sousTotalCmd = $montantTotal;
                $montantTotal = $sousTotalCmd + $fraisLivraison;

                $cmd1 = Cmd1::create([
                    'id_client' => $client->id,
                    'id_frs' => $frs->id,
                    'date_cmd' => now(),
                    'statut' => 'en_attente',
                    'montant_total' => $montantTotal,
                    'sous_total' => $sousTotalCmd,
                    'frais_livraison' => $fraisLivraison,
                    'adresse_livraison' => $data['adresse_livraison'],
                    'id_wilaya' => (int) $data['id_wilaya'],
                    'id_commune' => (int) $data['id_commune'],
                    'notes' => $data['notes'] ?? null,
                    'synced_pme' => 0,
                ]);

                foreach ($lines as $line) {
                    Cmd2::create([
                        'id_cmd' => $cmd1->id,
                        'id_produit' => $line['id_produit'],
                        'quantite' => $line['quantite'],
                        'prix_unitaire' => $line['prix_unitaire'],
                        'sous_total' => $line['sous_total'],
                    ]);
                }

                $frs->notify(new NouvelleCommande($cmd1));

                $lignes = Cmd2::query()
                    ->leftJoin('produit', 'produit.id', '=', 'cmd2.id_produit')
                    ->select([
                        'cmd2.id',
                        'cmd2.id_produit',
                        'cmd2.quantite',
                        'cmd2.prix_unitaire',
                        'cmd2.sous_total',
                        'produit.designation as produit_designation',
                        'produit.reference as produit_reference',
                    ])
                    ->where('cmd2.id_cmd', $cmd1->id)
                    ->orderBy('cmd2.id')
                    ->get();

                return [
                    'commande' => [
                        'id' => $cmd1->id,
                        'id_frs' => $cmd1->id_frs,
                        'id_client' => $cmd1->id_client,
                        'date_cmd' => (string) $cmd1->date_cmd,
                        'statut' => (string) $cmd1->statut,
                        'montant_total' => (float) $cmd1->montant_total,
                        'sous_total' => (float) ($cmd1->sous_total ?? 0),
                        'frais_livraison' => (float) ($cmd1->frais_livraison ?? 0),
                        'adresse_livraison' => $cmd1->adresse_livraison,
                        'id_wilaya' => (int) $cmd1->id_wilaya,
                        'id_commune' => (int) $cmd1->id_commune,
                        'notes' => $cmd1->notes,
                        'synced_pme' => (int) $cmd1->synced_pme,
                    ],
                    'lignes' => $lignes,
                ];
            });
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 400);
        } catch (\Throwable $e) {
            return $this->serverError();
        }

        return $this->success($result, 'Commande créée', 201);
    }

    public function index(Request $request)
    {
        $client = $request->user();

        $paginator = Cmd1::query()
            ->leftJoin('frs', 'frs.id', '=', 'cmd1.id_frs')
            ->select([
                'cmd1.*',
                'frs.nom_frs as nom_frs',
            ])
            ->where('cmd1.id_client', $client->id)
            ->orderByDesc('cmd1.date_cmd')
            ->paginate(20);

        $items = $paginator->getCollection()->map(function ($c) {
            return [
                'id' => $c->id,
                'id_frs' => (int) $c->id_frs,
                'nom_frs' => $c->nom_frs,
                'date_cmd' => (string) $c->date_cmd,
                'statut' => (string) $c->statut,
                'montant_total' => (float) $c->montant_total,
                'sous_total' => (float) ($c->sous_total ?? 0),
                'frais_livraison' => (float) ($c->frais_livraison ?? 0),
                'adresse_livraison' => $c->adresse_livraison,
                'synced_pme' => (int) $c->synced_pme,
            ];
        })->values();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Mes commandes');
    }

    public function show(Request $request, int $id)
    {
        $client = $request->user();

        $cmd = Cmd1::query()
            ->where('id', $id)
            ->where('id_client', $client->id)
            ->first();

        if (! $cmd) {
            return $this->notFound();
        }

        $lignes = Cmd2::query()
            ->leftJoin('produit', 'produit.id', '=', 'cmd2.id_produit')
            ->select([
                'cmd2.id',
                'cmd2.id_produit',
                'cmd2.quantite',
                'cmd2.prix_unitaire',
                'cmd2.sous_total',
                'produit.designation as produit_designation',
                'produit.reference as produit_reference',
                'produit.image_principale as produit_image',
            ])
            ->where('cmd2.id_cmd', $cmd->id)
            ->orderBy('cmd2.id')
            ->get();

        return $this->success([
            'commande' => [
                'id' => $cmd->id,
                'id_frs' => (int) $cmd->id_frs,
                'date_cmd' => (string) $cmd->date_cmd,
                'statut' => (string) $cmd->statut,
                'montant_total' => (float) $cmd->montant_total,
                'sous_total' => (float) ($cmd->sous_total ?? 0),
                'frais_livraison' => (float) ($cmd->frais_livraison ?? 0),
                'adresse_livraison' => $cmd->adresse_livraison,
                'id_wilaya' => (int) $cmd->id_wilaya,
                'id_commune' => (int) $cmd->id_commune,
                'notes' => $cmd->notes,
                'synced_pme' => (int) $cmd->synced_pme,
            ],
            'lignes' => $lignes,
        ], 'Détail commande');
    }
}
