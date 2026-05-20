@extends('store.layout')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ url('/') }}" class="text-sm text-slate-500 hover:text-slate-900">
            <i class="fa-solid fa-arrow-left-long mr-2"></i>
            Retour
        </a>
        <a href="{{ url('/panier') }}"
           class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50">
            <i class="fa-solid fa-cart-shopping text-[var(--store-primary)]"></i>
            <span>Panier</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] overflow-hidden">
            <div class="relative aspect-[4/3] bg-slate-100" id="galleryRoot">
                @if(count($images) > 0)
                    <img id="galleryMainImage" src="{{ $images[0] }}" alt="" class="w-full h-full object-cover">
                    @if(count($images) > 1)
                        <button type="button"
                                id="galleryPrevBtn"
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-10 w-10 rounded-xl bg-white/90 border border-slate-200 text-slate-800 hover:bg-white shadow"
                                aria-label="Photo précédente">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button type="button"
                                id="galleryNextBtn"
                                class="absolute right-3 top-1/2 -translate-y-1/2 h-10 w-10 rounded-xl bg-white/90 border border-slate-200 text-slate-800 hover:bg-white shadow"
                                aria-label="Photo suivante">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                        <div class="absolute bottom-3 right-3 rounded-xl bg-black/55 text-white text-xs font-extrabold px-3 py-1.5">
                            <span id="galleryCounter">1/{{ count($images) }}</span>
                        </div>
                    @endif
                @else
                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                        <i class="fa-regular fa-image text-4xl"></i>
                    </div>
                @endif
            </div>
            @if(count($images) > 1)
                <div class="p-3 border-t border-slate-200 bg-slate-50 overflow-x-auto">
                    <div class="flex items-center gap-2 min-w-max">
                        @foreach($images as $i => $u)
                            <button type="button"
                                    class="h-14 w-16 rounded-xl border border-slate-200 bg-white overflow-hidden flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-[var(--store-primary)]"
                                    data-gallery-thumb="{{ $i }}"
                                    aria-label="Afficher la photo {{ $i + 1 }}">
                                <img src="{{ $u }}" alt="" class="h-14 w-16 object-cover">
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-6">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500">
                        {{ $produit->categorie ?: '—' }}
                    </span>
                    @if($produit->subCategory)
                        <span class="inline-flex text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600">
                            {{ $produit->subCategory->nom }}
                        </span>
                    @endif
                    @if($produit->brand)
                        <span class="inline-flex text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600">
                            <i class="fa-solid fa-copyright mr-1.5"></i>{{ $produit->brand->nom }}
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
                    {{ $produit->designation }}
                </h1>
            </div>
            <div class="mt-1 text-sm text-slate-600">Ref: {{ $produit->reference }}</div>

            @php
                $initialQty = (int) ($initialQty ?? 1);
                $initialUnit = (float) ($initialUnit ?? $produit->prixUnitairePourQuantite($client ?? null, $initialQty));

                $tiers = $tiers ?? ($produit->relationLoaded('quantityPrices') ? $produit->quantityPrices : $produit->quantityPrices()->get(['quantity_min', 'quantity_max', 'price']))
                    ->map(fn ($t) => [
                        'quantity_min' => (int) $t->quantity_min,
                        'quantity_max' => $t->quantity_max === null ? null : (int) $t->quantity_max,
                        'price' => (float) $t->price,
                    ])
                    ->values()
                    ->all();

                $tierEnabled = (bool) ($tierEnabled ?? ($produit->isTierPricingEnabled() && count($tiers) > 0));
            @endphp

            <div class="mt-4 flex items-center justify-between gap-3">
                @if(($can_show_prices ?? false) || ($client ?? null))
                    <div class="text-2xl font-extrabold">
                        <span id="unitPrice">{{ number_format($initialUnit, 2, '.', ' ') }}</span> DA
                    </div>
                @else
                    <div class="text-sm font-extrabold text-slate-500">
                        Connectez-vous pour voir le prix
                    </div>
                @endif
                <span class="text-xs font-bold px-2.5 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-600">
                    {{ $produit->categorie ?: '—' }}
                </span>
            </div>
            @if(($can_show_prices ?? false) || ($client ?? null))
                <div class="mt-1 text-xs text-slate-500">
                    Total: <span class="font-bold text-slate-700"><span id="totalPrice">{{ number_format($initialUnit * $initialQty, 2, '.', ' ') }}</span> DA</span>
                </div>
            @endif

            <div class="mt-2 text-sm {{ (int)$produit->stock > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                {{ (int)$produit->stock > 0 ? ('Stock disponible: '.(int)$produit->stock) : 'Rupture de stock' }}
            </div>

            <div class="mt-5 text-sm text-slate-700 leading-relaxed">
                {{ trim((string)$produit->description) !== '' ? $produit->description : '—' }}
            </div>

            @if($tierEnabled && (($can_show_prices ?? false) || ($client ?? null)))
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="font-extrabold tracking-wide">Tarifs par quantité</div>
                    <div class="mt-3 space-y-2 text-sm">
                        @foreach($tiers as $t)
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-slate-600">
                                    @if($t['quantity_max'] === null)
                                        {{ (int)$t['quantity_min'] }}+ pièces
                                    @else
                                        {{ (int)$t['quantity_min'] }}-{{ (int)$t['quantity_max'] }} pièces
                                    @endif
                                </div>
                                <div class="font-extrabold">{{ number_format((float)$t['price'], 2, '.', ' ') }} DA</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <form method="POST"
                      action="{{ url('/panier/add') }}"
                      id="addToCartForm"
                      class="flex items-center gap-2"
                      data-pixel-product-id="{{ $produit->id }}"
                      data-pixel-price="{{ (($can_show_prices ?? false) || ($client ?? null)) ? (float)$produit->prixUnitairePourQuantite($client ?? null, 1) : 0 }}">
                    @csrf
                    <input type="hidden" name="produit_id" value="{{ $produit->id }}">
                    <input type="number"
                           name="qty"
                           id="qtyInput"
                           min="1"
                           max="{{ max(1, (int)$produit->stock) }}"
                           value="1"
                           class="w-24 rounded-xl border border-slate-200 bg-white px-3 py-2 outline-none focus:border-[var(--store-primary)]">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-extrabold text-white disabled:opacity-40"
                            style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);"
                            @disabled((int)$produit->stock <= 0)>
                        <i class="fa-solid fa-cart-plus"></i>
                        Ajouter au panier
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if(count($images) > 1)
    <script>
        (function () {
            const images = @json($images);
            const root = document.getElementById('galleryRoot');
            const img = document.getElementById('galleryMainImage');
            const prev = document.getElementById('galleryPrevBtn');
            const next = document.getElementById('galleryNextBtn');
            const counter = document.getElementById('galleryCounter');
            const thumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));

            if (!root || !img || !Array.isArray(images) || images.length < 2) return;

            let index = 0;

            function setActive(i) {
                const n = images.length;
                index = ((i % n) + n) % n;
                img.src = images[index];
                if (counter) counter.textContent = String(index + 1) + '/' + String(n);
                thumbs.forEach((btn, idx) => {
                    if (idx === index) {
                        btn.classList.add('ring-2', 'ring-[var(--store-primary)]');
                    } else {
                        btn.classList.remove('ring-2', 'ring-[var(--store-primary)]');
                    }
                });
            }

            if (prev) prev.addEventListener('click', () => setActive(index - 1));
            if (next) next.addEventListener('click', () => setActive(index + 1));
            thumbs.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const i = Number(btn.getAttribute('data-gallery-thumb'));
                    if (Number.isFinite(i)) setActive(i);
                });
            });

            let startX = null;
            root.addEventListener('touchstart', (e) => {
                if (!e.touches || e.touches.length !== 1) return;
                startX = e.touches[0].clientX;
            }, { passive: true });
            root.addEventListener('touchend', (e) => {
                if (startX === null) return;
                const x = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0].clientX : null;
                if (x === null) { startX = null; return; }
                const dx = x - startX;
                startX = null;
                if (Math.abs(dx) < 40) return;
                if (dx < 0) setActive(index + 1);
                else setActive(index - 1);
            }, { passive: true });

            setActive(0);
        })();
    </script>
@endif

@if(($can_show_prices ?? false) || ($client ?? null))
    <script>
        (function () {
            const qtyInput = document.getElementById('qtyInput');
            const unitEl = document.getElementById('unitPrice');
            const totalEl = document.getElementById('totalPrice');
            const formEl = document.getElementById('addToCartForm');

            if (!qtyInput || !unitEl || !totalEl) return;

            const enableTier = @json($tierEnabled);
            const tiers = @json($tiers);
            const baseUnit = Number(@json($initialUnit));

            function matchTier(qty) {
                if (!enableTier) return null;
                const sorted = [...tiers].sort((a, b) => Number(a.quantity_min) - Number(b.quantity_min));
                for (let i = sorted.length - 1; i >= 0; i--) {
                    const t = sorted[i];
                    const min = Number(t.quantity_min);
                    const max = (t.quantity_max === null || t.quantity_max === '') ? null : Number(t.quantity_max);
                    if (qty < min) continue;
                    if (max === null || qty <= max) return Number(t.price);
                }
                return null;
            }

            function fmt(v) {
                const n = Number(v);
                if (!Number.isFinite(n)) return '0,00';
                return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function update() {
                const qty = Math.max(1, Number(qtyInput.value || 1));
                const unit = matchTier(qty) ?? baseUnit;
                unitEl.textContent = fmt(unit);
                totalEl.textContent = fmt(unit * qty);
                if (formEl) {
                    formEl.setAttribute('data-pixel-price', String(unit));
                }
            }

            qtyInput.addEventListener('input', update);
            update();
        })();
    </script>
@endif
@endsection
