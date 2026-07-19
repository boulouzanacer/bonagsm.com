@extends('layouts.fournisseur')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-6">
        <form method="POST" action="{{ url('/fournisseur/categories') }}" class="space-y-4">
            @include('fournisseur.categories._form', ['categorie' => $categorie])

            <div class="flex flex-col sm:flex-row gap-2">
                <button class="flex-1 rounded-2xl px-4 py-3 font-extrabold text-white"
                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    {{ __('Créer') }}
                </button>
                <a href="{{ url('/fournisseur/categories') }}"
                   class="flex-1 text-center rounded-2xl px-4 py-3 font-extrabold border border-white/10 hover:bg-white/10">
                    {{ __('Annuler') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
