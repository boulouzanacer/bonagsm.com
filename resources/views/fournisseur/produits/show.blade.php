@extends('layouts.fournisseur')

@section('content')
@php
    $canEdit = (string)session('role', '') === 'fournisseur' || (int)session('is_admin', 0) === 1;
    $returnParam = trim((string) request('return', ''));
    $defaultReturn = url('/fournisseur/produits');
    $safeReturn = (\Illuminate\Support\Str::startsWith($returnParam, $defaultReturn) || \Illuminate\Support\Str::startsWith($returnParam, $defaultReturn.'?'))
        ? $returnParam
        : $defaultReturn;
    $tiers = ($produit->relationLoaded('quantityPrices') ? $produit->quantityPrices : collect())
        ->map(fn ($t) => [
            'quantity_min' => (int) $t->quantity_min,
            'quantity_max' => $t->quantity_max === null ? null : (int) $t->quantity_max,
            'price' => (float) $t->price,
        ])
        ->values()
        ->all();
    $tierEnabled = $produit->isTierPricingEnabled() && count($tiers) > 0;
@endphp

<div class="max-w-4xl space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-2xl font-extrabold tracking-wide">Détail produit</div>
            <div class="text-sm text-white/60">{{ $produit->designation }} • {{ $produit->reference }}</div>
        </div>
        <div class="flex items-center gap-2">
            @if($canEdit)
                <a href="{{ url('/fournisseur/produits/'.$produit->id.'/edit').'?'.http_build_query(['return' => $safeReturn]) }}"
                   class="rounded-2xl px-4 py-3 font-bold text-white"
                   style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    Modifier
                </a>
            @endif
            <a href="{{ $safeReturn }}"
               class="rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10">
                Retour
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-white/10 bg-black/20 overflow-hidden">
                @php
                    $main = trim((string) ($produit->image_principale ?? ''));
                @endphp
                <div class="h-56 bg-black/20">
                    @if($main !== '')
                        <img src="{{ $main }}" alt="" class="h-56 w-full object-cover">
                    @else
                        <div class="h-56 w-full flex items-center justify-center text-white/40">
                            <i class="fa-solid fa-image text-2xl"></i>
                        </div>
                    @endif
                </div>
                @if(isset($images) && count($images) > 0)
                    <div class="p-3 border-t border-white/10 grid grid-cols-5 gap-2">
                        @foreach($images as $img)
                            @php($u = trim((string)($img->url_principale ?? '')))
                            @if($u !== '')
                                <img src="{{ $u }}" alt="" class="h-12 w-full object-cover rounded-xl border border-white/10">
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm text-white/60">Catégorie</div>
                    <div class="font-extrabold">{{ $produit->categorie ?: '—' }}</div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm text-white/60">Stock</div>
                    <div class="font-extrabold">{{ (int)$produit->stock }}</div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm text-white/60">PV 1</div>
                    <div class="font-extrabold">{{ number_format((float)$produit->pv_1, 2, '.', ' ') }}</div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm text-white/60">PV 2</div>
                    <div class="font-extrabold">{{ number_format((float)$produit->pv_2, 2, '.', ' ') }}</div>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm text-white/60">PV 3</div>
                    <div class="font-extrabold">{{ number_format((float)$produit->pv_3, 2, '.', ' ') }}</div>
                </div>

                <div class="pt-2">
                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $tierEnabled ? 'border-sky-400/20 bg-sky-500/15 text-sky-200' : 'border-white/10 bg-white/5 text-white/70' }}">
                        <i class="fa-solid {{ $tierEnabled ? 'fa-check' : 'fa-xmark' }}"></i>
                        Prix par palier: {{ $tierEnabled ? 'Activé' : 'Désactivé' }}
                    </span>
                </div>
            </div>
        </div>

        <div>
            <div class="text-sm font-extrabold text-white/80">Description</div>
            <div class="mt-2 text-sm text-white/70 leading-relaxed">
                {{ trim((string)$produit->description) !== '' ? $produit->description : '—' }}
            </div>
        </div>

        @if($tierEnabled)
            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="font-extrabold tracking-wide">Tarifs par quantité</div>
                    <div class="text-xs text-white/50">{{ count($tiers) }} palier(s)</div>
                </div>
                <div class="mt-3 space-y-2 text-sm">
                    @foreach($tiers as $t)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-[var(--frs-card)] px-4 py-3">
                            <div class="text-white/70">
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
    </div>
</div>
@endsection
