<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\ProduitImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ImageProduitService
{
    public static function publicUrl(?string $raw): string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return '';
        }

        $lower = strtolower($v);
        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
            $path = (string) parse_url($v, PHP_URL_PATH);
            if ($path !== '' && str_starts_with($path, '/storage/')) {
                return url($path);
            }
            return $v;
        }

        if (str_starts_with($v, '//')) {
            return request()->getScheme().':'.$v;
        }

        if (str_starts_with($v, '/')) {
            return url($v);
        }

        return Storage::url($v);
    }

    public function storeUploadedImages(Produit $produit, int $frsId, array $uploadedFiles, ?array $orders = null, ?string $primaryKey = null): void
    {
        $manager = null;
        try {
            if (extension_loaded('gd')) {
                $manager = ImageManager::gd();
            } elseif (extension_loaded('imagick')) {
                $manager = ImageManager::imagick();
            }
        } catch (\Throwable) {
            $manager = null;
        }

        $newKeys = [];
        $newRecordsByKey = [];

        foreach ($uploadedFiles as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $base = Str::uuid()->toString().'_'.now()->timestamp.'.webp';
            $thumb = Str::uuid()->toString().'_'.now()->timestamp.'_thumb.webp';

            $dir = "produits/{$frsId}";

            if ($manager) {
                try {
                    $img = $manager->read($file->getPathname())->cover(800, 800);
                    Storage::disk('public')->put("{$dir}/{$base}", (string) $img->toWebp(80));

                    $thumbImg = $manager->read($file->getPathname())->cover(200, 200);
                    Storage::disk('public')->put("{$dir}/{$thumb}", (string) $thumbImg->toWebp(80));
                } catch (\Throwable) {
                    $manager = null;
                }
            }

            if (! $manager) {
                $ext = strtolower((string) $file->getClientOriginalExtension());
                if ($ext === '') {
                    $ext = 'jpg';
                }
                $base = Str::uuid()->toString().'_'.now()->timestamp.'.'.$ext;
                $principalPath = $file->storeAs($dir, $base, 'public');
                $thumb = $base;
                $thumbPath = $principalPath;
            } else {
                $principalPath = "{$dir}/{$base}";
                $thumbPath = "{$dir}/{$thumb}";
            }

            $record = ProduitImage::create([
                'id_produit' => $produit->id,
                'filename' => $base,
                'url_principale' => $principalPath,
                'url_thumbnail' => $thumbPath,
                'ordre' => 0,
            ]);

            $key = 'new:'.$index;
            $newKeys[] = $key;
            $newRecordsByKey[$key] = $record;
        }

        $existing = ProduitImage::query()
            ->where('id_produit', $produit->id)
            ->orderBy('ordre')
            ->get();

        $items = [];
        foreach ($existing as $img) {
            $items['existing:'.$img->id] = $img;
        }
        foreach ($newKeys as $k) {
            $items[$k] = $newRecordsByKey[$k];
        }

        $orderedKeys = [];
        if (is_array($orders) && count($orders) > 0) {
            foreach ($orders as $k) {
                if (isset($items[$k])) {
                    $orderedKeys[] = $k;
                }
            }
            foreach (array_keys($items) as $k) {
                if (! in_array($k, $orderedKeys, true)) {
                    $orderedKeys[] = $k;
                }
            }
        } else {
            $orderedKeys = array_keys($items);
        }

        foreach ($orderedKeys as $i => $k) {
            $items[$k]->update(['ordre' => $i]);
        }

        if ($primaryKey && isset($items[$primaryKey])) {
            $produit->update(['image_principale' => $items[$primaryKey]->url_principale]);
        } else {
            $first = $items[$orderedKeys[0]] ?? null;
            if ($first && empty($produit->image_principale)) {
                $produit->update(['image_principale' => $first->url_principale]);
            }
        }
    }

    public function deleteImages(Produit $produit, int $frsId, array $imageIds): void
    {
        $dir = "produits/{$frsId}";

        $images = ProduitImage::query()
            ->where('id_produit', $produit->id)
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $img) {
            $principalPath = $this->normalizeToPublicDiskPath($img->url_principale, $dir);
            $thumbPath = $this->normalizeToPublicDiskPath($img->url_thumbnail, $dir);

            if ($principalPath) {
                Storage::disk('public')->delete($principalPath);
            }
            if ($thumbPath) {
                Storage::disk('public')->delete($thumbPath);
            }

            $img->delete();
        }

        $remaining = ProduitImage::query()
            ->where('id_produit', $produit->id)
            ->orderBy('ordre')
            ->get();

        foreach ($remaining as $i => $img) {
            $img->update(['ordre' => $i]);
        }

        $produit->refresh();
        if ($produit->image_principale) {
            $exists = ProduitImage::query()
                ->where('id_produit', $produit->id)
                ->where('url_principale', $produit->image_principale)
                ->exists();

            if (! $exists) {
                $first = $remaining->first();
                $produit->update(['image_principale' => $first?->url_principale]);
            }
        }
    }

    private function normalizeToPublicDiskPath(?string $raw, string $dir): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        $lower = strtolower($value);
        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://') || str_starts_with($value, '//')) {
            $value = (string) parse_url($value, PHP_URL_PATH);
        }

        $needle = '/storage/';
        if (str_starts_with($value, $needle)) {
            $relative = ltrim(substr($value, strlen($needle)), '/');
        } else {
            $relative = ltrim($value, '/');
        }

        if (! str_starts_with($relative, $dir.'/')) {
            return null;
        }

        return $relative;
    }
}
