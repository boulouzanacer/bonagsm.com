@extends('store.layout')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <section class="rounded-[28px] border border-slate-200/80 bg-white/95 p-5 sm:p-6 lg:p-7 shadow-[0_16px_45px_rgba(15,23,42,0.06)]">
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="text-[2rem] font-extrabold tracking-tight text-slate-900 sm:text-[2.2rem]">{{ __('Produits') }}</div>
                    <div class="mt-1 text-sm text-slate-600 sm:text-base">
                        @if(($client ?? null))
                            {{ __('Parcourez vos produits, retrouvez vos tarifs et ajoutez rapidement au panier.') }}
                        @else
                            {{ __('Parcourez le catalogue et ajoutez au panier.') }}
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:min-w-[300px]">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
                        <div class="text-sm font-medium text-slate-500">{{ __('Panier') }}</div>
                        <div class="mt-1 text-[1.35rem] font-extrabold tracking-tight text-slate-900">{{ $cart_count }} {{ __('produit(s)') }}</div>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
                        <div class="text-sm font-medium text-slate-500">{{ __('Total') }}</div>
                        @if(($can_show_prices ?? false) || ($client ?? null))
                            <div class="force-ltr mt-1 text-[1.35rem] font-extrabold tracking-tight text-slate-900">{{ number_format((float)$cart_total, 2, '.', ' ') }} DA</div>
                        @else
                            <div class="mt-1 text-[1.35rem] font-extrabold tracking-tight text-[var(--store-primary)]">{{ __('Connectez-vous') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ url('/') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr),390px]">
                <div class="relative">
                    <i class="store-search-icon fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input name="q"
                           value="{{ $q }}"
                           placeholder="{{ __('Rechercher référence/désignation/catégorie...') }}"
                           class="store-search-input w-full rounded-2xl border border-slate-200 bg-slate-50/60 pl-11 pr-4 py-3.5 text-slate-900 outline-none transition focus:border-[var(--store-primary)] focus:bg-white focus:ring-4 focus:ring-emerald-100">
                </div>

                <div>
                    <select name="categorie" class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3.5 text-slate-900 outline-none transition focus:border-[var(--store-primary)] focus:bg-white focus:ring-4 focus:ring-emerald-100">
                        <option value="">{{ __('Toutes catégories') }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c }}" @selected((string)$selected_categorie === (string)$c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <button class="hidden" type="submit">{{ __('Search') }}</button>
            </form>

            @if(count($categories) > 0)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ url('/').'?'.http_build_query(array_filter(['q' => $q])) }}"
                       class="rounded-full px-3.5 py-1.5 text-xs font-bold border transition {{ $selected_categorie === '' ? 'border-emerald-300 bg-emerald-50 text-emerald-700 shadow-sm shadow-emerald-100/70' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50/50 hover:text-slate-900' }}">
                        {{ __('Tous') }}
                    </a>
                    @foreach($categories as $c)
                        <a href="{{ url('/').'?'.http_build_query(array_filter(['q' => $q, 'categorie' => $c])) }}"
                           class="rounded-full px-3.5 py-1.5 text-xs font-bold border transition {{ (string)$selected_categorie === (string)$c ? 'border-emerald-300 bg-emerald-50 text-emerald-700 shadow-sm shadow-emerald-100/70' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50/50 hover:text-slate-900' }}">
                            {{ $c }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <div class="space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-lg font-extrabold tracking-wide text-slate-900">{{ __('Produits') }}</div>
                <div class="text-sm text-slate-500">{{ __('Catalogue optimisé pour mobile, tablette et desktop.') }}</div>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-3 py-2 text-sm text-slate-500 shadow-sm">
                <i class="fa-solid fa-box-open text-[var(--store-primary)]"></i>
                <span class="force-ltr">{{ $produits->total() }} {{ __('produit(s)') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 min-[420px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 sm:gap-4">
            @forelse($produits as $p)
                @php
                    $img = \App\Services\ImageProduitService::publicUrl($p->image_principale ?? '');
                    $isFavorite = in_array((int) $p->id, $wishlist_ids ?? [], true);
                @endphp
                <div class="interactive-lift soft-card group rounded-[24px] overflow-hidden">
                    <a href="{{ url('/produits/'.$p->id) }}" class="block">
                        <div class="relative aspect-[1/1] overflow-hidden bg-gradient-to-br from-slate-100 via-white to-emerald-50">
                            <form method="POST" action="{{ url($isFavorite ? '/wishlist/remove' : '/wishlist/add') }}" class="absolute right-3 top-3 z-10">
                                @csrf
                                <input type="hidden" name="produit_id" value="{{ $p->id }}">
                                <button type="submit"
                                        aria-label="{{ $isFavorite ? __('Retirer des favoris') : __('Ajouter aux favoris') }}"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/80 bg-white/90 text-sm shadow-sm transition hover:scale-105 {{ $isFavorite ? 'text-rose-500' : 'text-slate-500 hover:text-[var(--store-primary)]' }}">
                                    <i class="{{ $isFavorite ? 'fa-solid' : 'fa-regular' }} fa-heart"></i>
                                </button>
                            </form>
                            @if($img !== '')
                                <img src="{{ $img }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fa-regular fa-image text-2xl"></i>
                                </div>
                            @endif
                            <div class="absolute left-3 top-3 inline-flex items-center rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-slate-600 shadow-sm">
                                {{ $p->categorie ?: __('Produit') }}
                            </div>
                        </div>
                    </a>
                    <div class="p-3 sm:p-4">
                        <div class="min-w-0">
                            <a href="{{ url('/produits/'.$p->id) }}" class="block text-sm font-extrabold leading-tight text-slate-900 hover:text-[var(--store-primary)]" title="{{ $p->designation }}">
                                {{ $p->designation }}
                            </a>
                            <div class="force-ltr mt-1 text-[11px] text-slate-400 truncate">{{ __('Ref:') }} {{ $p->reference }}</div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <div>
                                @if(($can_show_prices ?? false) || ($client ?? null))
                                    <div class="force-ltr font-extrabold text-base text-slate-900 whitespace-nowrap">
                                        {{ number_format((float)$p->prixUnitairePourQuantite($client ?? null, 1), 2, '.', ' ') }} <span class="text-[10px] opacity-70">DA</span>
                                    </div>
                                @else
                                    <div class="text-[11px] font-bold text-slate-400 whitespace-nowrap">{{ __('Connectez-vous') }}</div>
                                @endif
                            </div>
                            <div class="force-ltr text-[11px] font-bold {{ (int)$p->stock > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ (int)$p->stock > 0 ? __('Stock: :stock', ['stock' => (int) $p->stock]) : __('Rupture') }}
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2">
                            <span class="inline-flex text-[10px] font-bold px-2.5 py-1 rounded-xl border border-slate-100 bg-slate-50 text-slate-500 truncate max-w-[120px]">
                                {{ $p->categorie ?: '—' }}
                            </span>

                            @php($pixelUnit = (($can_show_prices ?? false) || ($client ?? null)) ? (float)$p->prixUnitairePourQuantite($client ?? null, 1) : 0.0)
                            <form method="POST"
                                  action="{{ url('/panier/add') }}"
                                  data-pixel-product-id="{{ $p->id }}"
                                  data-pixel-price="{{ $pixelUnit }}">
                                @csrf
                                <input type="hidden" name="produit_id" value="{{ $p->id }}">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit"
                                        aria-label="{{ __('Ajouter au panier') }}"
                                        class="interactive-lift inline-flex items-center justify-center gap-2 rounded-2xl min-w-[42px] h-[42px] sm:px-4 text-xs font-extrabold text-white store-gradient disabled:opacity-40 shadow-lg shadow-emerald-950/15"
                                        @disabled((int)$p->stock <= 0)>
                                    <i class="fa-solid fa-cart-plus"></i>
                                    <span class="sr-only sm:not-sr-only">{{ __('Ajouter') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full soft-card rounded-[28px] p-10 text-center text-slate-600">
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
