@extends('store.layout')

@section('content')
<div class="space-y-6 sm:space-y-8">
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <section class="soft-card rounded-[28px] p-5 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.2em] text-emerald-700">{{ __('Checkout') }}</div>
            <div class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Finaliser votre commande') }}</div>
            <div class="mt-2 text-sm text-slate-600">
                @if($boutique)
                    {{ __('Boutique:') }} <span class="font-semibold text-slate-900">{{ $boutique->nom_frs }}</span>
                @endif
            </div>
        </div>
        <a href="{{ url('/panier') }}" class="interactive-lift inline-flex items-center gap-2 rounded-2xl border border-white/70 bg-white px-4 py-2.5 text-sm text-slate-500 shadow-sm hover:text-slate-900">
            <i class="fa-solid fa-arrow-left-long"></i>
            {{ __('Retour panier') }}
        </a>
    </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
        <div class="lg:col-span-2 soft-card rounded-[28px] p-6">
            <div class="text-lg font-extrabold tracking-wide">{{ __('Adresse de livraison') }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ __('Complétez vos informations pour confirmer la livraison.') }}</div>
            <form method="POST" action="{{ url('/checkout') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Adresse') }}</label>
                    <input name="adresse_livraison"
                           value="{{ old('adresse_livraison', $client->adresse ?? '') }}"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 outline-none transition focus:border-[var(--store-primary)] focus:ring-4 focus:ring-emerald-100"
                           required>
                    @error('adresse_livraison')
                        <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Wilaya') }}</label>
                        <select id="wilayaSelect"
                                name="id_wilaya"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 outline-none transition focus:border-[var(--store-primary)] focus:ring-4 focus:ring-emerald-100"
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
                        <label class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Commune') }}</label>
                        <select id="communeSelect"
                                name="id_commune"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 outline-none transition focus:border-[var(--store-primary)] focus:ring-4 focus:ring-emerald-100"
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
                    <label class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Notes (optionnel)') }}</label>
                    <textarea name="notes"
                              rows="4"
                              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none transition focus:border-[var(--store-primary)] focus:ring-4 focus:ring-emerald-100">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit"
                        class="interactive-lift w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3.5 text-sm font-extrabold text-white store-gradient shadow-lg shadow-emerald-950/15">
                    <i class="fa-solid fa-cart-shopping"></i>
                    {{ __('Confirmer la commande') }}
                </button>
            </form>
        </div>

        <div class="soft-card rounded-[28px] p-6 h-fit lg:sticky lg:top-24">
            <div class="text-lg font-extrabold tracking-wide">{{ __('Récapitulatif') }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ __('Contrôlez les lignes et le total avant validation.') }}</div>
            <div class="mt-4 space-y-3">
                @foreach($items as $it)
                    @php($p = $it['produit'])
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="font-bold truncate">{{ $p->designation }}</div>
                            <div class="text-slate-500">x{{ (int)$it['qty'] }}</div>
                        </div>
                        <div class="font-extrabold">{{ number_format((float)$it['line_total'], 2, '.', ' ') }} DA</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 pt-4 border-t border-slate-200 space-y-3">
                <div class="flex items-center justify-between text-slate-600">
                    <span>{{ __('Sous-total') }}</span>
                    <span class="font-extrabold text-slate-900" id="subtotalEl">{{ number_format((float)$total, 2, '.', ' ') }} DA</span>
                </div>

                @if(($shipping_enabled ?? false))
                    <div class="flex items-center justify-between text-slate-600">
                        <span>{{ __('Frais de livraison') }}</span>
                        <span class="font-extrabold text-slate-900" id="shippingFeeEl">{{ number_format((float)($shipping_fee ?? 0), 2, '.', ' ') }} DA</span>
                    </div>
                    <div class="text-[11px] text-slate-500" id="shippingMotifEl"></div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="text-slate-600">{{ __('Total') }}</span>
                    <span class="font-extrabold text-slate-900 text-xl" id="totalEl">{{ number_format((float)($total_with_shipping ?? $total), 2, '.', ' ') }} DA</span>
                </div>
            </div>

            <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
                {{ __('Paiement à la livraison.') }}
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const wilayaSelect = document.getElementById('wilayaSelect');
    const communeSelect = document.getElementById('communeSelect');
    if (!wilayaSelect || !communeSelect) return;

    const shippingEnabled = '{{ (int) ((bool)($shipping_enabled ?? false)) }}' === '1';
    const shippingFees = JSON.parse('{{ addslashes(json_encode($shipping_fees ?? [])) }}');
    const subtotal = Number('{{ (float) $total }}');
    const contents = JSON.parse('{{ addslashes(json_encode($pixel_contents ?? [])) }}');
    const shippingEl = document.getElementById('shippingFeeEl');
    const motifEl = document.getElementById('shippingMotifEl');
    const totalEl = document.getElementById('totalEl');

    const setLoading = (loading) => {
        communeSelect.disabled = loading;
        if (loading) {
            communeSelect.innerHTML = '<option value=\"\">{{ __('Chargement...') }}</option>';
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
            communeSelect.innerHTML = '<option value=\"\">{{ __('Erreur') }}</option>';
            setLoading(false);
        }
    };

    wilayaSelect.addEventListener('change', () => {
        const id = wilayaSelect.value;
        if (!id) return;
        loadCommunes(id);
        if (shippingEnabled) {
            const fee = Number(shippingFees[id] ?? 0);
            if (shippingEl) shippingEl.textContent = fee.toLocaleString(@json(app()->getLocale() === 'ar' ? 'ar-DZ' : 'fr-FR'), { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
            if (totalEl) totalEl.textContent = (subtotal + fee).toLocaleString(@json(app()->getLocale() === 'ar' ? 'ar-DZ' : 'fr-FR'), { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
            const label = (wilayaSelect.selectedOptions && wilayaSelect.selectedOptions[0]) ? wilayaSelect.selectedOptions[0].textContent.trim() : id;
            if (motifEl) motifEl.textContent = @json(__('Motif: Livraison vers :label', ['label' => ''])) + label;
        }
    });

    if (shippingEnabled) {
        const id = wilayaSelect.value;
        const fee = Number(shippingFees[id] ?? 0);
        if (shippingEl) shippingEl.textContent = fee.toLocaleString(@json(app()->getLocale() === 'ar' ? 'ar-DZ' : 'fr-FR'), { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
        if (totalEl) totalEl.textContent = (subtotal + fee).toLocaleString(@json(app()->getLocale() === 'ar' ? 'ar-DZ' : 'fr-FR'), { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DA';
        const label = (wilayaSelect.selectedOptions && wilayaSelect.selectedOptions[0]) ? wilayaSelect.selectedOptions[0].textContent.trim() : id;
        if (motifEl) motifEl.textContent = @json(__('Motif: Livraison vers :label', ['label' => ''])) + label;
    }

    if (typeof window.trackInitiateCheckout === 'function') {
        const fee = shippingEnabled ? Number(shippingFees[wilayaSelect.value] ?? 0) : 0;
        window.trackInitiateCheckout({ value: subtotal + fee, contents: contents });
    }
})();
</script>
@endsection
