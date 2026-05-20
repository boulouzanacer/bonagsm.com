<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SousCategorie extends Model
{
    use HasFactory;

    protected $table = 'sous_categories';

    protected $fillable = [
        'id_frs',
        'id_categorie',
        'nom',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'id_frs', 'id');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'id_categorie', 'id');
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'id_sous_categorie', 'id');
    }
}
