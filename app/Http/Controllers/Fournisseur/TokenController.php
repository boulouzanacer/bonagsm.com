<?php

namespace App\Http\Controllers\Fournisseur;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Contracts\View\View;

class TokenController extends Controller
{
    public function index(): View
    {
        $frs = Fournisseur::query()->findOrFail((int) session('frs_id'));

        return view('fournisseur.token', [
            'title' => 'Mon Token PME',
            'token' => $frs->token,
            'endpoint' => url('/api/v1/pme'),
        ]);
    }
}
