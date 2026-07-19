@extends('store.layout')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <a href="{{ url('/') }}" class="text-sm text-slate-500 hover:text-slate-900 inline-flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left-long"></i>
                    {{ __('Retour au store') }}
                </a>
                <div class="mt-3 flex items-start gap-4">
                    @if(($boutique->logo_url ?? '') !== '')
                        <img src="{{ $boutique->logo_url }}"
                             alt=""
                             class="h-14 w-14 rounded-2xl object-cover border border-slate-200 bg-white flex-shrink-0">
                    @else
                        <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-white flex-shrink-0"
                             style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);">
                            <i class="fa-solid fa-store"></i>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-2xl font-extrabold tracking-wide truncate">{{ $boutique->nom_frs }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $boutique->adresse ?? '—' }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $boutique->telephone ?? '—' }}</div>
                        @if(($boutique->latitude ?? null) && ($boutique->longitude ?? null))
                            <a href="https://www.google.com/maps?q={{ $boutique->latitude }},{{ $boutique->longitude }}"
                               target="_blank"
                               class="mt-1 inline-flex items-center gap-2 text-sm font-semibold text-[var(--store-primary)] hover:underline">
                                <i class="fa-solid fa-location-dot"></i>
                                {{ __('Voir sur Maps') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ url('/panier') }}"
                   class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-[var(--store-primary)]"></i>
                    <span>{{ __('Panier') }}</span>
                </a>
            </div>
        </div>

        <form method="GET" action="{{ url('/boutiques/'.$boutique->id) }}" class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-3">
            <div class="lg:col-span-2">
                <div class="relative">
                    <i class="store-search-icon fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input name="q"
                           value="{{ $q }}"
                           placeholder="{{ __('Rechercher référence/désignation/catégorie...') }}"
                           class="store-search-input w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 py-3 outline-none focus:border-[var(--store-primary)]">
                </div>
            </div>
            <div>
                <select name="categorie" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]">
                    <option value="">{{ __('Toutes catégories') }}</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" @selected((string)$selected_categorie === (string)$c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <button class="hidden" type="submit">{{ __('Filter') }}</button>
        </form>
    </div>

    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="text-lg font-extrabold tracking-wide">{{ __('Produits') }}</div>
            <div class="text-sm text-slate-500">{{ $produits->total() }} {{ __('produit(s)') }}</div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-2">
            @forelse($produits as $p)
                @php
                    $img = \App\Services\ImageProduitService::publicUrl($p->image_principale ?? '');
                @endphp
                <div class="rounded-xl border border-slate-200 bg-[var(--store-card)] overflow-hidden">
                    <a href="{{ url('/produits/'.$p->id) }}" class="block">
                        <div class="aspect-square bg-slate-100">
                            @if($img !== '')
                                <img src="{{ $img }}" alt="" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fa-regular fa-image text-2xl"></i>
                                </div>
                            @endif
                        </div>
                    </a>
                    <div class="p-2">
                        <div class="min-w-0">
                            <a href="{{ url('/produits/'.$p->id) }}" class="block font-extrabold text-xs sm:text-[13px] leading-tight hover:underline truncate" title="{{ $p->designation }}">
                                {{ $p->designation }}
                            </a>
                            <div class="text-[10px] text-slate-400 truncate">{{ __('Ref:') }} {{ $p->reference }}</div>
                        </div>

                        <div class="mt-1.5 flex items-center justify-between gap-2">
                            <div>
                                <div class="font-extrabold text-xs sm:text-[14px] text-slate-900 whitespace-nowrap">
                                    {{ number_format((float)$p->prixUnitairePourQuantite($client ?? null, 1), 2, '.', ' ') }} <span class="text-[10px] opacity-70">DA</span>
                                </div>
                            </div>
                            <div class="hidden sm:block text-[10px] font-bold {{ (int)$p->stock > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ (int)$p->stock > 0 ? __('Stock: :stock', ['stock' => (int) $p->stock]) : __('Rupture') }}
                            </div>
                        </div>

                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <span class="hidden sm:inline-flex text-[10px] font-bold px-2 py-0.5 rounded-lg border border-slate-100 bg-slate-50 text-slate-500 truncate max-w-[80px]">
                                {{ $p->categorie ?: '—' }}
                            </span>
                            @php($pixelUnit = (float)$p->prixUnitairePourQuantite($client ?? null, 1))
                            <form method="POST"
                                  action="{{ url('/panier/add') }}"
                                  data-pixel-product-id="{{ $p->id }}"
                                  data-pixel-price="{{ $pixelUnit }}">
                                @csrf
                                <input type="hidden" name="produit_id" value="{{ $p->id }}">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit"
                                        aria-label="{{ __('Ajouter au panier') }}"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl w-9 h-9 sm:w-auto sm:h-auto sm:px-2.5 sm:py-2 text-xs font-extrabold text-white disabled:opacity-40"
                                        style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);"
                                        @disabled((int)$p->stock <= 0)>
                                    <i class="fa-solid fa-cart-plus"></i>
                                    <span class="sr-only sm:not-sr-only">{{ __('Ajouter') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200 bg-[var(--store-card)] p-10 text-center text-slate-600">
                    {{ __('Aucun produit.') }}
                </div>
            @endforelse
        </div>

        <div class="pt-2">
            {{ $produits->links() }}
        </div>
    </div>
</div>
@endsection
