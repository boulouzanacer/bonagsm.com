@extends('layouts.fournisseur')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <form method="GET" action="{{ url('/fournisseur/categories') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full lg:w-auto">
            <div class="md:col-span-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/50"></i>
                    <input name="q"
                           value="{{ $q }}"
                           placeholder="{{ __('Rechercher catégorie...') }}"
                           class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] pl-11 pr-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>
            </div>
            <div class="flex gap-2 md:col-span-3">
                <button class="flex-1 rounded-2xl px-4 py-3 font-bold text-white"
                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    {{ __('Filtrer') }}
                </button>
                <a href="{{ url('/fournisseur/categories') }}"
                   class="rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10">
                    {{ __('Réinitialiser') }}
                </a>
            </div>
        </form>

        <a href="{{ url('/fournisseur/categories/create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 font-bold text-white"
           style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
            <i class="fa-solid fa-plus"></i>
            {{ __('Nouvelle catégorie') }}
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        <div class="divide-y divide-white/10">
            @forelse($categories as $c)
                <div class="p-4 flex items-center justify-between gap-3">
                    <div>
                        <div class="font-extrabold">{{ $c->nom }}</div>
                        <div class="text-xs text-white/50 space-y-0.5">
                            <div>{{ $c->slug }}</div>
                            <div>
                                <span class="{{ (int)$c->used_products_count > 0 ? 'text-amber-300' : 'text-white/40' }}">
                                    {{ __('Produits: :count', ['count' => (int)$c->used_products_count]) }}
                                </span>
                                <span class="mx-2 text-white/20">·</span>
                                <span class="{{ (int)$c->used_sub_categories_count > 0 ? 'text-amber-300' : 'text-white/40' }}">
                                    {{ __('Sous-catégories: :count', ['count' => (int)$c->used_sub_categories_count]) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ url('/fournisseur/categories/'.$c->id.'/edit') }}"
                           class="h-9 w-9 inline-flex items-center justify-center rounded-xl text-xs font-bold border border-white/10 hover:bg-white/10"
                           title="{{ __('Modifier') }}">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form method="POST" action="{{ url('/fournisseur/categories/'.$c->id) }}">
                            @csrf
                            @method('DELETE')
                            @if((bool) $c->can_delete)
                                <button type="submit"
                                        onclick="return confirm(@js(__('Supprimer cette catégorie ?')))"
                                        class="h-9 w-9 inline-flex items-center justify-center rounded-xl text-xs font-bold border border-red-400/20 text-red-300 hover:bg-red-500/10"
                                        title="{{ __('Supprimer') }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            @else
                                <button type="button"
                                        disabled
                                        class="h-9 w-9 inline-flex items-center justify-center rounded-xl text-xs font-bold border border-white/10 text-white/40 bg-white/5 cursor-not-allowed"
                                        title="{{ __('Supprimer: catégorie utilisée') }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            @endif
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-white/60">
                    {{ __('Aucune catégorie.') }}
                </div>
            @endforelse
        </div>
    </div>

    <div>
        {{ $categories->links() }}
    </div>
</div>
@endsection
