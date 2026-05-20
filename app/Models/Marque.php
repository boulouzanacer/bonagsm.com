<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marque extends Model
{
    use HasFactory;

    protected $table = 'marques';

    protected $fillable = [
        'id_frs',
        'nom',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'id_frs', 'id');
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'id_marque', 'id');
    }
}
