<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingsController extends Controller
{
    public function edit(): View
    {
        $frs = Fournisseur::query()->findOrFail((int) session('frs_id'));

        return view('fournisseur.parametres-site', [
            'title' => 'Paramètres Site Web',
            'frs' => $frs,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $frs = Fournisseur::query()->findOrFail((int) session('frs_id'));

        $data = $request->validate([
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'show_prices_to_guests' => ['nullable', 'boolean'],
            'meta_pixel_id' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:50', 'regex:/^[0-9A-Za-z]+$/'],
        ]);

        $payload = [
            'show_prices_to_guests' => (int) ($data['show_prices_to_guests'] ?? 0) === 1 ? 1 : 0,
            'meta_pixel_id' => isset($data['meta_pixel_id']) && trim((string) $data['meta_pixel_id']) !== '' ? trim((string) $data['meta_pixel_id']) : null,
            'tiktok_pixel_id' => isset($data['tiktok_pixel_id']) && trim((string) $data['tiktok_pixel_id']) !== '' ? trim((string) $data['tiktok_pixel_id']) : null,
        ];

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

        $frs->update($payload);

        return back()->with('success', __('Paramètres du site mis à jour.'));
    }
}
