<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\FraisLivraison;
use App\Models\Wilaya;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FraisLivraisonController extends Controller
{
    public function index(): View
    {
        $frsId = (int) session('frs_id');
        $frs = DB::table('frs')->where('id', $frsId)->first(['id', 'enable_frais_livraison']);

        $wilayas = Wilaya::query()->orderBy('ID_WILAYA')->get(['ID_WILAYA', 'WILAYA']);

        $fees = FraisLivraison::query()
            ->where('id_frs', $frsId)
            ->get(['id_wilaya', 'frais'])
            ->keyBy(fn ($r) => (int) $r->id_wilaya);

        return view('fournisseur.frais-livraison', [
            'title' => 'Frais de livraison',
            'enabled' => (int) ($frs->enable_frais_livraison ?? 0) === 1,
            'wilayas' => $wilayas,
            'fees' => $fees,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'fees' => ['nullable', 'array'],
            'fees.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $enabled = (int) ($data['enabled'] ?? 0) === 1 ? 1 : 0;
        $fees = is_array($data['fees'] ?? null) ? $data['fees'] : [];

        DB::transaction(function () use ($frsId, $enabled, $fees) {
            DB::table('frs')->where('id', $frsId)->update(['enable_frais_livraison' => $enabled]);

            if ($enabled !== 1) {
                return;
            }

            foreach ($fees as $wilayaId => $value) {
                $idWilaya = (int) $wilayaId;
                if ($idWilaya <= 0) {
                    continue;
                }

                $f = $value === null || $value === '' ? 0.0 : (float) $value;
                if ($f < 0) {
                    $f = 0.0;
                }

                FraisLivraison::query()->updateOrCreate(
                    ['id_frs' => $frsId, 'id_wilaya' => $idWilaya],
                    ['frais' => $f]
                );
            }
        });

        return back()->with('success', __('Enregistré.'));
    }
}
