@extends('store.layout')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('Mes favoris') }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ __('Retrouvez vos produits sauvegardés et ajoutez-les rapidement au panier.') }}</div>
        </div>
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-2xl border border-white/70 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-white">
            <i class="rtl-flip fa-solid fa-arrow-left-long text-[var(--store-primary)]"></i>
            {{ __('Retour au catalogue') }}
        </a>
    </div>

    @if($produits->count() > 0)
        <div class="grid grid-cols-1 min-[420px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 sm:gap-4">
            @foreach($produits as $p)
                @php
                    $img = \App\Services\ImageProduitService::publicUrl($p->image_principale ?? '');
                    $isFavorite = in_array((int) $p->id, $wishlist_ids ?? [], true);
                @endphp
                <div class="interactive-lift soft-card group rounded-[24px] overflow-hidden">
                    <a href="{{ url('/produits/'.$p->id) }}" class="block">
                        <div class="relative aspect-[1/1] overflow-hidden bg-gradient-to-br from-slate-100 via-white to-emerald-50">
                            <form method="POST" action="{{ url('/wishlist/remove') }}" class="card-favorite-float absolute right-3 top-3 z-10">
                                @csrf
                                <input type="hidden" name="produit_id" value="{{ $p->id }}">
                                <button type="submit"
                                        aria-label="{{ __('Retirer des favoris') }}"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/80 bg-white/90 text-sm text-rose-500 shadow-sm transition hover:scale-105">
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </form>
                            @if($img !== '')
                                <img src="{{ $img }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fa-regular fa-image text-2xl"></i>
                                </div>
                            @endif
                        </div>
                    </a>
                    <div class="p-3 sm:p-4">
                        <a href="{{ url('/produits/'.$p->id) }}" class="block text-sm font-extrabold leading-tight text-slate-900 hover:text-[var(--store-primary)]">
                            {{ $p->designation }}
                        </a>
                        <div class="force-ltr mt-1 text-[11px] text-slate-400 truncate">{{ __('Ref:') }} {{ $p->reference }}</div>

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

                            <form method="POST"
                                  action="{{ url('/panier/add') }}"
                                  data-pixel-product-id="{{ $p->id }}"
                                  data-pixel-price="{{ (($can_show_prices ?? false) || ($client ?? null)) ? (float)$p->prixUnitairePourQuantite($client ?? null, 1) : 0 }}">
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
            @endforeach
        </div>

        <div class="pt-2">
            {{ $produits->links() }}
        </div>
    @else
        <div class="soft-card rounded-[28px] p-10 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-[var(--store-primary)]">
                <i class="fa-regular fa-heart text-2xl"></i>
            </div>
            <div class="mt-4 text-xl font-extrabold text-slate-900">{{ __('Aucun favori pour le moment') }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ __('Ajoutez des produits a vos favoris pour les retrouver rapidement plus tard.') }}</div>
            <a href="{{ url('/') }}" class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white hover:bg-emerald-700">
                <i class="fa-solid fa-store"></i>
                {{ __('Voir le catalogue') }}
            </a>
        </div>
    @endif
</div>
@endsection
