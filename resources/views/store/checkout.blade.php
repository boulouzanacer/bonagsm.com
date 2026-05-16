@extends('store.layout')

@section('content')
<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <div class="text-2xl font-extrabold tracking-wide">Finaliser la commande</div>
            <div class="mt-1 text-sm text-slate-600">
                @if($boutique)
                    Boutique: <span class="font-semibold text-slate-900">{{ $boutique->nom_frs }}</span>
                @endif
            </div>
        </div>
        <a href="{{ url('/panier') }}" class="text-sm text-slate-500 hover:text-slate-900">
            <i class="fa-solid fa-arrow-left-long mr-2"></i>
            Retour panier
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-[var(--store-card)] p-6">
            <div class="text-lg font-extrabold tracking-wide">Adresse de livraison</div>
            <form method="POST" action="{{ url('/checkout') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Adresse</label>
                    <input name="adresse_livraison"
                           value="{{ old('adresse_livraison', $client->adresse ?? '') }}"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]"
                           required>
                    @error('adresse_livraison')
                        <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Wilaya</label>
                        <select id="wilayaSelect"
                                name="id_wilaya"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]"
                                required>
                            @foreach($wilayas as $w)
                                <option value="{{ $w->ID_WILAYA }}"
                                        @selected((int)old('id_wilaya', $selected_wilaya) === (int)$w->ID_WILAYA)>
                                    {{ $w->ID_WILAYA }} - {{ $w->WILAYA }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_wilaya')
                            <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Commune</label>
                        <select id="communeSelect"
                                name="id_commune"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]"
                                required>
                            @foreach($communes as $c)
                                <option value="{{ $c->ID_COMMUNE }}"
                                        @selected((int)old('id_commune', $client->id_commune ?? 0) === (int)$c->ID_COMMUNE)>
                                    {{ $c->COMMUNE }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_commune')
                            <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes (optionnel)</label>
                    <textarea name="notes"
                              rows="4"
                              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-extrabold text-white"
                        style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);">
                    <i class="fa-solid fa-cart-shopping"></i>
                    Confirmer la commande
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-6 h-fit">
            <div class="text-lg font-extrabold tracking-wide">Récapitulatif</div>
            <div class="mt-4 space-y-3">
                @foreach($items as $it)
                    @php($p = $it['produit'])
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <div class="font-bold truncate">{{ $p->designation }}</div>
                            <div class="text-slate-500">x{{ (int)$it['qty'] }}</div>
                        </div>
                        <div class="font-extrabold">{{ number_format((float)$it['line_total'], 2, '.', ' ') }} DA</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 pt-4 border-t border-slate-200 space-y-2">
                <div class="flex items-center justify-between text-slate-600">
                    <span>Sous-total</span>
                    <span class="font-extrabold text-slate-900" id="subtotalEl">{{ number_format((float)$total, 2, '.', ' ') }} DA</span>
                </div>

                @if(($shipping_enabled ?? false))
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Frais de livraison</span>
                        <span class="font-extrabold text-slate-900" id="shippingFeeEl">{{ number_format((float)($shipping_fee ?? 0), 2, '.', ' ') }} DA</span>
                    </div>
                    <div class="text-[11px] text-slate-500" id="shippingMotifEl"></div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Total</span>
                    <span class="font-extrabold text-slate-900" id="totalEl">{{ number_format((float)($total_with_shipping ?? $total), 2, '.', ' ') }} DA</span>
                </div>
            </div>

            <div class="mt-3 text-xs text-slate-500">
                Paiement à la livraison.
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const wilayaSelect = document.getElementById('wilayaSelect');
    const communeSelect = document.getElementById('communeSelect');
    if (!wilayaSelect || !communeSelect) return;

    const shippingEnabled = @json((bool)($shipping_enabled ?? false));
    const shippingFees = @json(($shipping_fees ?? []));
    const subtotal = Number(@json((float)$total));
    const contents = @json(($pixel_contents ?? []));
    const shippingEl = document.getElementById('shippingFeeEl');
    const motifEl = document.getElementById('shippingMotifEl');
    const totalEl = document.getElementById('totalEl');

    const setLoading = (loading) => {
        communeSelect.disabled = loading;
        if (loading) {
            communeSelect.innerHTML = '<option value=\"\">Chargement...</option>';
        }
    };

    const loadCommunes = async (wilayaId) => {
        setLoading(true);
        try {
            const res = await fetch(`/api/v1/communes/${wilayaId}`, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            const rows = (json && json.success && Array.isArray(json.data)) ? json.data : [];
            communeSelect.innerHTML = rows.map(c => `<option value=\"${c.ID_COMMUNE}\">${c.COMMUNE}</option>`).join('');
            setLoading(false);
        } catch (_) {
            communeSelect.innerHTML = '<option value=\"\">Erreur</option>';
            setLoading(false);
        }
    };

    wilayaSelect.addEventListener('change', () => {
        const id = wilayaSelect.value;
        if (!id) return;
        loadCommunes(id);
        if (shippingEnabled) {
            const fee = Number(shippingFees[id] ?? 0);
            if (shippingEl) shippingEl.textContent = fee.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
            if (totalEl) totalEl.textContent = (subtotal + fee).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
            const label = (wilayaSelect.selectedOptions && wilayaSelect.selectedOptions[0]) ? wilayaSelect.selectedOptions[0].textContent.trim() : id;
            if (motifEl) motifEl.textContent = 'Motif: Livraison vers ' + label;
        }
    });

    if (shippingEnabled) {
        const id = wilayaSelect.value;
        const fee = Number(shippingFees[id] ?? 0);
        if (shippingEl) shippingEl.textContent = fee.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
        if (totalEl) totalEl.textContent = (subtotal + fee).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
        const label = (wilayaSelect.selectedOptions && wilayaSelect.selectedOptions[0]) ? wilayaSelect.selectedOptions[0].textContent.trim() : id;
        if (motifEl) motifEl.textContent = 'Motif: Livraison vers ' + label;
    }

    if (typeof window.trackInitiateCheckout === 'function') {
        const fee = shippingEnabled ? Number(shippingFees[wilayaSelect.value] ?? 0) : 0;
        window.trackInitiateCheckout({ value: subtotal + fee, contents: contents });
    }
})();
</script>
@endsection
