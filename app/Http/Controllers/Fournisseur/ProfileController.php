<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Commune;
use App\Models\Fournisseur;
use App\Models\Wilaya;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $frs = Fournisseur::query()->findOrFail((int) session('frs_id'));

        $wilayas = Wilaya::query()->orderBy('ID_WILAYA')->get();
        $communes = Commune::query()
            ->where('ID_WILAYA', $frs->id_wilaya)
            ->orderBy('COMMUNE')
            ->get();

        return view('fournisseur.profil', [
            'title' => 'Mon Profil',
            'frs' => $frs,
            'wilayas' => $wilayas,
            'communes' => $communes,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $frs = Fournisseur::query()->findOrFail((int) session('frs_id'));

        $data = $request->validate([
            'nom_frs' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'show_prices_to_guests' => ['nullable', 'boolean'],
            'meta_pixel_id' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:50', 'regex:/^[0-9A-Za-z]+$/'],
            'adresse' => ['required', 'string'],
            'id_wilaya' => ['required', 'integer', 'exists:wilaya,ID_WILAYA'],
            'id_commune' => ['required', 'integer', 'exists:commune,ID_COMMUNE'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $payload = [
            'nom_frs' => $data['nom_frs'],
            'telephone' => $data['telephone'] ?? null,
            'show_prices_to_guests' => (int) ($data['show_prices_to_guests'] ?? 0) === 1 ? 1 : 0,
            'meta_pixel_id' => isset($data['meta_pixel_id']) && trim((string) $data['meta_pixel_id']) !== '' ? trim((string) $data['meta_pixel_id']) : null,
            'tiktok_pixel_id' => isset($data['tiktok_pixel_id']) && trim((string) $data['tiktok_pixel_id']) !== '' ? trim((string) $data['tiktok_pixel_id']) : null,
            'adresse' => $data['adresse'],
            'id_wilaya' => (int) $data['id_wilaya'],
            'id_commune' => (int) $data['id_commune'],
            'latitude' => array_key_exists('latitude', $data) ? (float) $data['latitude'] : null,
            'longitude' => array_key_exists('longitude', $data) ? (float) $data['longitude'] : null,
            'is_visible' => 1,
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

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $frs = Fournisseur::query()->findOrFail((int) session('frs_id'));

        $data = $request->validate([
            'old_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['old_password'], $frs->password)) {
            return back()->withErrors(['old_password' => 'Ancien mot de passe incorrect.']);
        }

        $frs->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Mot de passe mis à jour.');
    }

    public function communes(int $idWilaya): JsonResponse
    {
        $rows = DB::table('commune')
            ->where('ID_WILAYA', $idWilaya)
            ->orderBy('COMMUNE')
            ->get(['ID_COMMUNE', 'COMMUNE']);

        return response()->json($rows);
    }
}
