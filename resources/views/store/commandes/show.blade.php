@extends('store.layout')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-2xl font-extrabold tracking-wide">Commande #{{ $commande->id }}</div>
            <div class="mt-1 text-sm text-slate-600">
                Boutique: <span class="font-semibold text-slate-900">{{ $commande->frs_nom ?? '—' }}</span>
                <span class="mx-2 text-slate-300">•</span>
                {{ \Illuminate\Support\Carbon::parse($commande->date_cmd)->format('d/m/Y H:i') }}
            </div>
        </div>
        <a href="{{ url('/mes-commandes') }}" class="text-sm text-slate-500 hover:text-slate-900">
            <i class="fa-solid fa-arrow-left-long mr-2"></i>
            Retour
        </a>
    </div>

    @php
        $statut = (string)$commande->statut;
        $badge = match($statut) {
            'en_attente' => 'bg-amber-50 text-amber-700 border border-amber-200',
            'confirmee' => 'bg-sky-50 text-sky-700 border border-sky-200',
            'expediee' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
            'livree' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'annulee' => 'bg-red-50 text-red-700 border border-red-200',
            default => 'bg-slate-50 text-slate-600 border border-slate-200'
        };
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-[var(--store-card)] overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div class="font-extrabold tracking-wide">Produits</div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $badge }}">{{ $statut }}</span>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach($lignes as $l)
                    <div class="p-5 flex items-start gap-4">
                        <div class="h-16 w-16 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 flex-shrink-0">
                            @if(($l->produit_image_url ?? '') !== '')
                                <img src="{{ $l->produit_image_url }}" alt="" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fa-regular fa-image"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-extrabold truncate">{{ $l->produit_designation ?? ('Produit #'.$l->id_produit) }}</div>
                                    <div class="mt-1 text-sm text-slate-600">Ref: {{ $l->produit_reference ?? '—' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-extrabold">{{ number_format((float)$l->sous_total, 2, '.', ' ') }} DA</div>
                                    <div class="text-xs text-slate-500">{{ (int)$l->quantite }} × {{ number_format((float)$l->prix_unitaire, 2, '.', ' ') }} DA</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-6">
                <div class="text-lg font-extrabold tracking-wide">Livraison</div>
                <div class="mt-3 text-sm text-slate-700 leading-relaxed">
                    {{ $commande->adresse_livraison ?? '—' }}
                </div>
                @if(trim((string)($commande->notes ?? '')) !== '')
                    <div class="mt-4 text-xs font-bold text-slate-500">Notes</div>
                    <div class="mt-1 text-sm text-slate-700">{{ $commande->notes }}</div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-6">
                <div class="text-lg font-extrabold tracking-wide">Total</div>
                @php
                    $sumLignes = (float) $lignes->sum('sous_total');
                    $sousTotal = (float) ($commande->sous_total ?? 0);
                    if ($sousTotal <= 0 && $sumLignes > 0) {
                        $sousTotal = $sumLignes;
                    }
                    $frais = (float) ($commande->frais_livraison ?? 0);
                @endphp
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Sous-total</span>
                        <span class="font-extrabold text-slate-900">{{ number_format($sousTotal, 2, '.', ' ') }} DA</span>
                    </div>
                    @if($frais > 0)
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Frais de livraison</span>
                            <span class="font-extrabold text-slate-900">{{ number_format($frais, 2, '.', ' ') }} DA</span>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Motif: Livraison vers {{ (int)($commande->id_wilaya ?? 0) }} - {{ $commande->wilaya_nom ?? '—' }}
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Total</span>
                        <span class="font-extrabold text-slate-900">{{ number_format((float)$commande->montant_total, 2, '.', ' ') }} DA</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('pixel_purchase') && (int)(session('pixel_purchase.order_id') ?? 0) === (int)$commande->id)
    <script>
        (function () {
            const payload = @json(session('pixel_purchase'));
            if (!payload) return;
            if (typeof window.trackPurchase === 'function') {
                window.trackPurchase(payload);
            }
        })();
    </script>
@endif
@endsection
