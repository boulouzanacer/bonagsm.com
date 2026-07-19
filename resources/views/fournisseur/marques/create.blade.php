@extends('layouts.fournisseur')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-6">
        <form method="POST" action="{{ $marque ? url('/fournisseur/marques/'.$marque->id) : url('/fournisseur/marques') }}" class="space-y-4">
            @csrf
            @if($marque) @method('PUT') @endif

            <div>
                <label class="block text-sm font-bold text-white/70 mb-2">{{ __('Nom de la marque') }}</label>
                <input type="text" name="nom" value="{{ old('nom', $marque->nom ?? '') }}" required
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-[var(--frs-primary)]">
                @error('nom') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="{{ url('/fournisseur/marques') }}" class="px-6 py-3 font-bold text-white/50 hover:text-white">{{ __('Annuler') }}</a>
                <button type="submit" class="rounded-xl px-8 py-3 font-bold text-white"
                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    {{ $marque ? __('Mettre à jour') : __('Enregistrer') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
