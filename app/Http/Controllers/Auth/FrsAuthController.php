<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use App\Models\FrsUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FrsAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $frs = Fournisseur::query()->where('email', $credentials['email'])->first();

        if ($frs && Hash::check($credentials['password'], $frs->password)) {
            if ((int) $frs->actif !== 1) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('Compte désactivé.')]);
            }

            $request->session()->regenerate();
            $request->session()->put([
                'role' => 'fournisseur',
                'frs_id' => $frs->id,
                'is_admin' => 1,
            ]);

            return redirect()->to('/fournisseur/dashboard');
        }

        $user = FrsUser::query()->where('email', $credentials['email'])->first();
        if ($user && Hash::check($credentials['password'], $user->password)) {
            if ((int) $user->actif !== 1) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('Compte désactivé.')]);
            }

            $request->session()->regenerate();
            $request->session()->put([
                'role' => 'frs_user',
                'frs_id' => (int) $user->id_frs,
                'frs_user_id' => (int) $user->id,
                'is_admin' => (string) $user->role === 'admin' ? 1 : 0,
            ]);

            return redirect()->to('/fournisseur/dashboard');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('Identifiants invalides.')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['role', 'frs_id', 'frs_user_id', 'is_admin']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to('/fournisseur/login');
    }
}
