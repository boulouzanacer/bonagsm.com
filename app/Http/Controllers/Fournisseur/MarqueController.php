<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Marque;
use App\Models\Produit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarqueController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));

        $marques = Marque::query()
            ->where('id_frs', $frsId)
            ->when($q !== '', fn ($query) => $query->where('nom', 'like', "%{$q}%"))
            ->orderBy('nom')
            ->paginate(20)
            ->withQueryString();

        return view('fournisseur.marques.index', [
            'title' => 'Marques',
            'q' => $q,
            'marques' => $marques,
        ]);
    }

    public function create(): View
    {
        return view('fournisseur.marques.create', [
            'title' => 'Créer marque',
            'marque' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marques', 'nom')->where(fn ($q) => $q->where('id_frs', $frsId)),
            ],
        ]);

        Marque::create([
            'id_frs' => $frsId,
            'nom' => trim($data['nom']),
        ]);

        return redirect()->to('/fournisseur/marques')->with('success', 'Marque créée.');
    }

    public function edit(int $id): View
    {
        $frsId = (int) session('frs_id');

        $marque = Marque::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        return view('fournisseur.marques.edit', [
            'title' => 'Éditer marque',
            'marque' => $marque,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $marque = Marque::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marques', 'nom')->where(fn ($q) => $q->where('id_frs', $frsId))->ignore($marque->id),
            ],
        ]);

        $marque->update([
            'nom' => trim($data['nom']),
        ]);

        return back()->with('success', 'Marque mise à jour.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $marque = Marque::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $used = Produit::query()
            ->where('id_frs', $frsId)
            ->where('id_marque', $marque->id)
            ->exists();

        if ($used) {
            return back()->with('error', 'Impossible de supprimer: marque utilisée par des produits.');
        }

        $marque->delete();

        return back()->with('success', 'Marque supprimée.');
    }
}
