@extends('layouts.fournisseur')

@section('content')
<div class="space-y-4">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="text-2xl font-extrabold tracking-wide">{{ $user->nom }}</div>
            <div class="force-ltr mt-1 text-sm text-white/60">{{ $user->email }}</div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/fournisseur/utilisateurs') }}"
               class="rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10">
                {{ __('Retour') }}
            </a>
            <a href="{{ url('/fournisseur/utilisateurs/'.$user->id.'/edit') }}"
               class="rounded-2xl px-4 py-3 font-extrabold text-white"
               style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                {{ __('Modifier') }}
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="flex items-center justify-between gap-3">
                <div class="text-white/60">{{ __('Rôle') }}</div>
                <div class="font-extrabold">{{ __((string)($user->role ?? 'user')) }}</div>
            </div>
            <div class="flex items-center justify-between gap-3">
                <div class="text-white/60">{{ __('Statut') }}</div>
                <div class="font-extrabold">{{ (int)($user->actif ?? 0) === 1 ? __('Actif') : __('Inactif') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
