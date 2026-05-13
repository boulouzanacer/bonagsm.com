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
            <div class="aspect-[4/3] bg-slate-100">
                @if(count($images) > 0)
                    <img src="{{ $images[0] }}" alt="" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                        <i class="fa-regular fa-image text-4xl"></i>
                    </div>
                @endif
            </div>
            @if(count($images) > 1)
                <div class="p-4 border-t border-slate-200 grid grid-cols-5 gap-2 bg-slate-50">
                    @foreach($images as $u)
                        <img src="{{ $u }}" alt="" class="h-14 w-full object-cover rounded-lg border border-slate-200">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-6">
            <div class="text-2xl font-extrabold tracking-wide">{{ $produit->designation }}</div>
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
