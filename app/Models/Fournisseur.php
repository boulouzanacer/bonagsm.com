<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Fournisseur extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;
    use Notifiable;

    protected $table = 'frs';

    protected $fillable = [
        'nom_frs',
        'email',
        'password',
        'telephone',
        'logo_path',
        'adresse',
        'id_wilaya',
        'id_commune',
        'latitude',
        'longitude',
        'token',
        'actif',
        'is_visible',
        'show_prices_to_guests',
        'enable_frais_livraison',
        'meta_pixel_id',
        'tiktok_pixel_id',
    ];

    protected $appends = [
        'logo_url',
        'google_maps_url',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    public function getLogoUrlAttribute(): string
    {
        $raw = trim((string) ($this->logo_path ?? ''));
        if ($raw === '') {
            return '';
        }

        $lower = strtolower($raw);
        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
            return $raw;
        }

        if (str_starts_with($raw, '/')) {
            return url($raw);
        }

        return Storage::url($raw);
    }

    public function getGoogleMapsUrlAttribute(): string
    {
        $lat = $this->latitude;
        $lng = $this->longitude;

        if ($lat === null || $lng === null) {
            return '';
        }

        return 'https://www.google.com/maps?q='.rawurlencode((string) $lat).','.rawurlencode((string) $lng);
    }

    public static function single(): ?self
    {
        $id = (int) (config('app.single_frs_id') ?? 0);

        $q = self::query()
            ->where('actif', 1)
            ->whereNull('deleted_at')
            ->orderBy('id');

        if ($id > 0) {
            return $q->where('id', $id)->first();
        }

        return $q->first();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->token)) {
                $model->token = (string) Str::uuid();
            }
        });
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'id_frs', 'id');
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'id_frs', 'id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Categorie::class, 'id_frs', 'id');
    }

    public function fraisLivraison(): HasMany
    {
        return $this->hasMany(FraisLivraison::class, 'id_frs', 'id');
    }

    public function cmd1(): HasMany
    {
        return $this->hasMany(Cmd1::class, 'id_frs', 'id');
    }
}
