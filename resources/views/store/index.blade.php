@extends('store.layout')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <section class="relative overflow-hidden rounded-[28px] border border-slate-200/80 bg-white p-5 sm:p-8 shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
        <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-emerald-500 via-emerald-600 to-slate-900"></div>
        <div class="absolute right-0 top-0 h-40 w-40 rounded-full bg-emerald-100/70 blur-3xl"></div>
        <div class="relative grid gap-6 lg:grid-cols-[1.45fr,0.85fr] lg:items-start">
            <div class="pl-2 sm:pl-4">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.25em] text-emerald-700">BonaGsm Store</div>
                <div class="mt-4 max-w-3xl text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-[3.2rem] lg:leading-[1.05]">
                    Une boutique plus claire, fluide et professionnelle.
                </div>
                <div class="mt-4 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    @if(($client ?? null))
                        Bienvenue {{ $client->prenom }} {{ $client->nom }}. Retrouvez vos produits, vos tarifs et vos commandes dans une interface plus lisible, plus légère et plus confortable.
                    @else
                        Parcourez le catalogue, trouvez rapidement vos produits et gérez votre panier dans une interface simple, élégante et agréable sur tous les écrans.
                    @endif
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Catalogue</div>
                        <div class="mt-1 text-xl font-extrabold text-slate-900">{{ $produits->total() }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Panier</div>
                        <div class="mt-1 text-xl font-extrabold text-slate-900">{{ $cart_count }} produit(s)</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Accès</div>
                        <div class="mt-1 text-xl font-extrabold text-slate-900">{{ ($client ?? null) ? 'Client' : 'Invité' }}</div>
                    </div>
                </div>
            </div>
            <div class="soft-card rounded-[24px] p-5 sm:p-6">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-400">Resume rapide</div>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Panier</div>
                            <div class="mt-1 text-sm font-semibold text-slate-500">Articles selectionnes</div>
                        </div>
                        <div class="text-lg font-extrabold text-slate-900">{{ $cart_count }}</div>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Total</div>
                            <div class="mt-1 text-sm font-semibold text-slate-500">Montant du panier</div>
                        </div>
                        @if(($can_show_prices ?? false) || ($client ?? null))
                            <div class="text-lg font-extrabold text-slate-900">{{ number_format((float)$cart_total, 2, '.', ' ') }} DA</div>
                        @else
                            <div class="text-sm font-extrabold text-emerald-700">Connexion requise</div>
                        @endif
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Compte</div>
                            <div class="mt-1 text-sm font-semibold text-slate-500">Statut actuel</div>
                        </div>
                        <div class="text-sm font-extrabold text-slate-900">{{ ($client ?? null) ? 'Connecte' : 'Invite' }}</div>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ url('/panier') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-emerald-700">
                        Voir le panier
                    </a>
                    @if(($client ?? null))
                        <a href="{{ url('/mes-commandes') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50">
                            Mes commandes
                        </a>
                    @else
                        <a href="{{ url('/login') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50">
                            Connexion
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="soft-card rounded-[28px] p-4 sm:p-6">
        <form method="GET" action="{{ url('/') }}" class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-3">
            <div class="lg:col-span-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input name="q"
                           value="{{ $q }}"
                           placeholder="Rechercher référence/désignation/catégorie..."
                           class="w-full rounded-2xl border border-slate-200/80 bg-white/90 pl-11 pr-4 py-3.5 outline-none transition focus:border-[var(--store-primary)] focus:ring-4 focus:ring-emerald-100">
                </div>
            </div>

            <div>
                <select name="categorie" class="w-full rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-3.5 outline-none transition focus:border-[var(--store-primary)] focus:ring-4 focus:ring-emerald-100">
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
                   class="interactive-lift rounded-full px-3.5 py-1.5 text-xs font-bold border {{ $selected_categorie === '' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    Tous
                </a>
                @foreach($categories as $c)
                    <a href="{{ url('/').'?'.http_build_query(array_filter(['q' => $q, 'categorie' => $c])) }}"
                       class="interactive-lift rounded-full px-3.5 py-1.5 text-xs font-bold border {{ (string)$selected_categorie === (string)$c ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        {{ $c }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-lg font-extrabold tracking-wide text-slate-900">Produits</div>
                <div class="text-sm text-slate-500">Catalogue optimisé pour mobile, tablette et desktop.</div>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-3 py-2 text-sm text-slate-500 shadow-sm">
                <i class="fa-solid fa-box-open text-[var(--store-primary)]"></i>
                <span>{{ $produits->total() }} produit(s)</span>
            </div>
        </div>

        <div class="grid grid-cols-1 min-[420px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 sm:gap-4">
            @forelse($produits as $p)
                @php
                    $img = \App\Services\ImageProduitService::publicUrl($p->image_principale ?? '');
                @endphp
                <div class="interactive-lift soft-card group rounded-[24px] overflow-hidden">
                    <a href="{{ url('/produits/'.$p->id) }}" class="block">
                        <div class="relative aspect-[1/1] overflow-hidden bg-gradient-to-br from-slate-100 via-white to-emerald-50">
                            @if($img !== '')
                                <img src="{{ $img }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fa-regular fa-image text-2xl"></i>
                                </div>
                            @endif
                            <div class="absolute left-3 top-3 inline-flex items-center rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-slate-600 shadow-sm">
                                {{ $p->categorie ?: 'Produit' }}
                            </div>
                        </div>
                    </a>
                    <div class="p-3 sm:p-4">
                        <div class="min-w-0">
                            <a href="{{ url('/produits/'.$p->id) }}" class="block text-sm font-extrabold leading-tight text-slate-900 hover:text-[var(--store-primary)]" title="{{ $p->designation }}">
                                {{ $p->designation }}
                            </a>
                            <div class="mt-1 text-[11px] text-slate-400 truncate">Ref: {{ $p->reference }}</div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <div>
                                @if(($can_show_prices ?? false) || ($client ?? null))
                                    <div class="font-extrabold text-base text-slate-900 whitespace-nowrap">
                                        {{ number_format((float)$p->prixUnitairePourQuantite($client ?? null, 1), 2, '.', ' ') }} <span class="text-[10px] opacity-70">DA</span>
                                    </div>
                                @else
                                    <div class="text-[11px] font-bold text-slate-400 whitespace-nowrap">Connectez-vous</div>
                                @endif
                            </div>
                            <div class="text-[11px] font-bold {{ (int)$p->stock > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ (int)$p->stock > 0 ? 'Stock: '.(int)$p->stock : 'Rupture' }}
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
                                        aria-label="Ajouter au panier"
                                        class="interactive-lift inline-flex items-center justify-center gap-2 rounded-2xl min-w-[42px] h-[42px] sm:px-4 text-xs font-extrabold text-white store-gradient disabled:opacity-40 shadow-lg shadow-emerald-950/15"
                                        @disabled((int)$p->stock <= 0)>
                                    <i class="fa-solid fa-cart-plus"></i>
                                    <span class="sr-only sm:not-sr-only">Ajouter</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full soft-card rounded-[28px] p-10 text-center text-slate-600">
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
