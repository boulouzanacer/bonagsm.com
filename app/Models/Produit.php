<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Produit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'produit';

    protected $fillable = [
        'id_frs',
        'reference',
        'designation',
        'description',
        'pv_1',
        'pv_2',
        'pv_3',
        'stock',
        'image_principale',
        'categorie',
        'id_categorie',
        'id_sous_categorie',
        'id_marque',
        'abonne_only',
        'enable_tier_pricing',
        'actif',
    ];

    protected $casts = [
        'pv_1' => 'float',
        'pv_2' => 'float',
        'pv_3' => 'float',
        'abonne_only' => 'integer',
        'enable_tier_pricing' => 'boolean',
        'actif' => 'integer',
    ];

    public function prixPourTarif(int $tarif): float
    {
        $t = $tarif;
        if ($t < 1 || $t > 3) {
            $t = 1;
        }

        return match ($t) {
            2 => (float) $this->pv_2,
            3 => (float) $this->pv_3,
            default => (float) $this->pv_1,
        };
    }

    public function prixPourClient(?Client $client): float
    {
        if (! $client) {
            return (float) $this->pv_1;
        }

        return $this->prixPourTarif((int) ($client->tarif ?? 1));
    }

    public function isTierPricingEnabled(): bool
    {
        if ((bool) ($this->enable_tier_pricing ?? false) === true) {
            return true;
        }

        if ($this->relationLoaded('quantityPrices')) {
            $tiers = $this->getRelation('quantityPrices');
            if ($tiers instanceof Collection) {
                return $tiers->isNotEmpty();
            }
        }

        return $this->quantityPrices()->exists();
    }

    public function prixUnitairePourQuantite(?Client $client, int $quantite): float
    {
        $qty = max(1, (int) $quantite);

        if ($this->isTierPricingEnabled()) {
            if ($this->relationLoaded('quantityPrices')) {
                /** @var Collection<int, ProduitQuantityPrice> $tiers */
                $tiers = $this->getRelation('quantityPrices');

                $match = $tiers
                    ->sortByDesc('quantity_min')
                    ->first(function (ProduitQuantityPrice $t) use ($qty) {
                        if ($t->quantity_min > $qty) {
                            return false;
                        }
                        if ($t->quantity_max === null) {
                            return true;
                        }
                        return $qty <= (int) $t->quantity_max;
                    });

                if ($match) {
                    return (float) $match->price;
                }
            } else {
                $match = $this->quantityPrices()
                    ->where('quantity_min', '<=', $qty)
                    ->where(function ($q) use ($qty) {
                        $q->whereNull('quantity_max')->orWhere('quantity_max', '>=', $qty);
                    })
                    ->orderByDesc('quantity_min')
                    ->first();

                if ($match) {
                    return (float) $match->price;
                }
            }
        }

        return $this->prixPourClient($client);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'id_frs', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'id_categorie', 'id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SousCategorie::class, 'id_sous_categorie', 'id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'id_marque', 'id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProduitImage::class, 'id_produit', 'id');
    }

    public function quantityPrices(): HasMany
    {
        return $this->hasMany(ProduitQuantityPrice::class, 'id_produit', 'id')->orderBy('quantity_min');
    }
}
