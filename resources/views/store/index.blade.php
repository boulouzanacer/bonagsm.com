@extends('store.layout')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="text-2xl font-extrabold tracking-wide">Produits</div>
                <div class="mt-1 text-sm text-slate-600">
                    @if(($client ?? null))
                        {{ $client->prenom }} {{ $client->nom }}
                        <span class="mx-2 text-slate-300">•</span>
                        <span class="font-semibold text-slate-900">{{ $client->type_client }}</span>
                    @else
                        Parcourez le catalogue et ajoutez au panier.
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs text-slate-500">Panier</div>
                    <div class="font-extrabold">{{ $cart_count }} produit(s)</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-xs text-slate-500">Total</div>
                    @if(($can_show_prices ?? false) || ($client ?? null))
                        <div class="font-extrabold">{{ number_format((float)$cart_total, 2, '.', ' ') }} DA</div>
                    @else
                        <div class="font-extrabold text-slate-500">Connectez-vous</div>
                    @endif
                </div>
            </div>
        </div>

        <form method="GET" action="{{ url('/') }}" class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-3">
            <div class="lg:col-span-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input name="q"
                           value="{{ $q }}"
                           placeholder="Rechercher référence/désignation/catégorie..."
                           class="w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 py-3 outline-none focus:border-[var(--store-primary)]">
                </div>
            </div>

            <div>
                <select name="categorie" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" @selected((string)$selected_categorie === (string)$c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <button class="hidden" type="submit">Search</button>
        </form>

        @if(count($categories) > 0)
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ url('/').'?'.http_build_query(array_filter(['q' => $q])) }}"
                   class="rounded-full px-3 py-1 text-xs font-bold border {{ $selected_categorie === '' ? 'border-[var(--store-primary)] bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    Tous
                </a>
                @foreach($categories as $c)
                    <a href="{{ url('/').'?'.http_build_query(array_filter(['q' => $q, 'categorie' => $c])) }}"
                       class="rounded-full px-3 py-1 text-xs font-bold border {{ (string)$selected_categorie === (string)$c ? 'border-[var(--store-primary)] bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        {{ $c }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="text-lg font-extrabold tracking-wide">Produits</div>
            <div class="text-sm text-slate-500">{{ $produits->total() }} produit(s)</div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-2">
            @forelse($produits as $p)
                @php
                    $raw = trim((string)($p->image_principale ?? ''));
                    $img = '';
                    if ($raw !== '') {
                        $lower = strtolower($raw);
                        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) $img = $raw;
                        elseif (str_starts_with($raw, '/')) $img = url($raw);
                        else $img = url('/'.$raw);
                    }
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
                            <div class="text-[10px] text-slate-400 truncate">Ref: {{ $p->reference }}</div>
                        </div>

                        <div class="mt-1.5 flex items-center justify-between gap-2">
                            <div>
                                @if(($can_show_prices ?? false) || ($client ?? null))
                                    <div class="font-extrabold text-xs sm:text-[14px] text-slate-900 whitespace-nowrap">
                                        {{ number_format((float)$p->prixUnitairePourQuantite($client ?? null, 1), 2, '.', ' ') }} <span class="text-[10px] opacity-70">DA</span>
                                    </div>
                                @else
                                    <div class="text-[10px] font-bold text-slate-400 whitespace-nowrap">Connectez-vous</div>
                                @endif
                            </div>
                            <div class="hidden sm:block text-[10px] font-bold {{ (int)$p->stock > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ (int)$p->stock > 0 ? 'Stock: '.(int)$p->stock : 'Rupture' }}
                            </div>
                        </div>

                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <span class="hidden sm:inline-flex text-[10px] font-bold px-2 py-0.5 rounded-lg border border-slate-100 bg-slate-50 text-slate-500 truncate max-w-[80px]">
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
                                        aria-label="Ajouter au panier"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl w-9 h-9 sm:w-auto sm:h-auto sm:px-2.5 sm:py-2 text-xs font-extrabold text-white disabled:opacity-40"
                                        style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);"
                                        @disabled((int)$p->stock <= 0)>
                                    <i class="fa-solid fa-cart-plus"></i>
                                    <span class="sr-only sm:not-sr-only">Ajouter</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200 bg-[var(--store-card)] p-10 text-center text-slate-600">
                    Aucun produit.
                </div>
            @endforelse
        </div>

        <div class="pt-2">
            {{ $produits->links() }}
        </div>
    </div>
</div>
@endsection
