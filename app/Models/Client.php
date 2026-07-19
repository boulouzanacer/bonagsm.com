<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;
    use Notifiable;
    use HasApiTokens;

    protected $table = 'client';

    protected $fillable = [
        'code_client',
        'nom',
        'prenom',
        'email',
        'email_verified_at',
        'email_verification_code_hash',
        'email_verification_expires_at',
        'password',
        'telephone',
        'adresse',
        'id_wilaya',
        'id_commune',
        'type_client',
        'tarif',
        'id_frs',
        'actif',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_expires_at' => 'datetime',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'id_frs', 'id');
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class, 'id_wilaya', 'ID_WILAYA');
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'id_commune', 'ID_COMMUNE');
    }

    public function wishlistProduits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'client_wishlist', 'id_client', 'id_produit')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }
}
