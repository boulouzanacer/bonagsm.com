@extends('layouts.fournisseur')

@section('content')
@php
    $canEdit = (string)session('role', '') === 'fournisseur' || (int)session('is_admin', 0) === 1;
@endphp
<div class="space-y-4">
    @if(($db_error ?? null))
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200">
            {{ $db_error }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <form method="GET" action="{{ url('/fournisseur/produits') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full lg:w-auto">
            <div class="md:col-span-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/50"></i>
                    <input name="q"
                           value="{{ $q }}"
                           placeholder="Rechercher désignation ou référence..."
                           class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] pl-11 pr-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>
            </div>

            <div>
                <select name="categorie"
                        class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected($categorie === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button class="flex-1 rounded-2xl px-4 py-3 font-bold text-white"
                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    Filtrer
                </button>
                <a href="{{ url('/fournisseur/produits') }}"
                   class="rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10">
                    Reset
                </a>
            </div>
        </form>

        @if($canEdit)
            <a href="{{ url('/fournisseur/produits/create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 font-bold text-white"
               style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                <i class="fa-solid fa-plus"></i>
                Nouveau produit
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-3">
        @forelse($produits as $p)
            @php
                $stock = (int) $p->stock;
                $stockBadge = $stock === 0
                    ? ['Rupture', 'bg-red-500/15 text-red-300 border border-red-400/20']
                    : ($stock < 5
                        ? ['Stock faible', 'bg-amber-500/15 text-amber-300 border border-amber-400/20']
                        : ['Disponible', 'bg-sky-500/15 text-sky-200 border border-sky-400/20']);
            @endphp

            <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
                <div class="relative h-24 bg-black/20">
                    @if($p->image_principale)
                        <img src="{{ $p->image_principale }}" class="h-24 w-full object-cover" alt="">
                    @else
                        <div class="h-24 w-full flex items-center justify-center text-white/40">
                            <i class="fa-solid fa-image text-xl"></i>
                        </div>
                    @endif

                    <div class="absolute top-2 left-2 flex flex-col gap-1">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $stockBadge[1] }}">{{ $stockBadge[0] }} ({{ $stock }})</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ (int)$p->actif === 1 ? 'bg-sky-500/15 text-sky-200 border border-sky-400/20' : 'bg-red-500/15 text-red-300 border border-red-400/20' }}">
                            {{ (int)$p->actif === 1 ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>

                <div class="p-3">
                    <div class="font-extrabold text-sm leading-tight truncate">{{ $p->designation }}</div>
                    <div class="text-[11px] text-white/60 mt-0.5 truncate">{{ $p->reference }} • {{ $p->categorie }}</div>

                    <div class="mt-2 flex items-center justify-between text-[11px]">
                        <div class="text-white/60">Prix</div>
                        <div class="font-extrabold">{{ number_format((float)$p->pv_1, 2, '.', ' ') }}</div>
                    </div>

                    <div class="mt-2 flex items-center justify-end gap-2">
                        <a href="{{ url('/fournisseur/produits/'.$p->id) }}"
                           class="h-8 w-8 inline-flex items-center justify-center rounded-xl text-xs font-bold border border-white/10 hover:bg-white/10"
                           title="Détails">
                            <i class="fa-solid fa-eye"></i>
                        </a>

                        @if($canEdit)
                            <a href="{{ url('/fournisseur/produits/'.$p->id.'/edit') }}"
                               class="h-8 w-8 inline-flex items-center justify-center rounded-xl text-xs font-bold border border-white/10 hover:bg-white/10"
                               title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <form method="POST" action="{{ url('/fournisseur/produits/'.$p->id.'/toggle-actif') }}">
                                @csrf
                                <button type="submit"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-xl text-xs font-bold border border-white/10 hover:bg-white/10"
                                        title="Activer/Désactiver">
                                    <i class="fa-solid {{ (int)$p->actif === 1 ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                            </form>

                            <form method="POST" action="{{ url('/fournisseur/produits/'.$p->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Supprimer ce produit ?')"
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-xl text-xs font-bold border border-red-400/20 text-red-300 hover:bg-red-500/10"
                                        title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-white/10 bg-[var(--frs-card)] p-10 text-center text-white/60">
                Aucun produit.
            </div>
        @endforelse
    </div>

    <div>
        {{ $produits->links() }}
    </div>
</div>
@endsection
