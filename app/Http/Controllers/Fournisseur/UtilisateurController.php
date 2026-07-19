<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\FrsUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    public function index(Request $request): View
    {
        $frsId = (int) session('frs_id');
        $q = trim((string) $request->query('q', ''));

        $users = FrsUser::query()
            ->where('id_frs', $frsId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nom', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('fournisseur.utilisateurs.index', [
            'title' => 'Gestion Utilisateurs',
            'users' => $users,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('fournisseur.utilisateurs.create', [
            'title' => 'Ajouter Utilisateur',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $frsId = (int) session('frs_id');
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:frs_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,user'],
            'actif' => ['nullable', 'boolean'],
        ]);

        FrsUser::create([
            'id_frs' => $frsId,
            'nom' => $data['nom'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'actif' => (int) ($data['actif'] ?? 0) === 1 ? 1 : 0,
        ]);

        return redirect()->to('/fournisseur/utilisateurs')->with('success', __('Utilisateur ajouté.'));
    }

    public function show(int $id): View
    {
        $frsId = (int) session('frs_id');

        $user = FrsUser::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        return view('fournisseur.utilisateurs.show', [
            'title' => 'Utilisateur',
            'user' => $user,
        ]);
    }

    public function edit(int $id): View
    {
        $frsId = (int) session('frs_id');

        $user = FrsUser::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        return view('fournisseur.utilisateurs.edit', [
            'title' => 'Modifier Utilisateur',
            'user' => $user,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $user = FrsUser::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:frs_users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,user'],
            'actif' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'nom' => $data['nom'],
            'email' => $data['email'],
            'role' => $data['role'],
            'actif' => (int) ($data['actif'] ?? 0) === 1 ? 1 : 0,
        ];

        if (isset($data['password']) && trim((string) $data['password']) !== '') {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return redirect()->to('/fournisseur/utilisateurs/'.$user->id)->with('success', __('Utilisateur mis à jour.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $frsId = (int) session('frs_id');

        $user = FrsUser::query()
            ->where('id_frs', $frsId)
            ->findOrFail($id);

        $user->delete();

        return redirect()->to('/fournisseur/utilisateurs')->with('success', __('Utilisateur supprimé.'));
    }
}
