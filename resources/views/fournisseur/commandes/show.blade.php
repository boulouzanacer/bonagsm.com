@extends('layouts.fournisseur')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-2xl font-extrabold tracking-wide">{{ __('Commande') }} #{{ $commande->id }}</div>
            <div class="text-sm text-white/60">{{ \Illuminate\Support\Carbon::parse($commande->date_cmd)->format('d/m/Y H:i') }}</div>
        </div>
        <a href="{{ url('/fournisseur/commandes') }}"
           class="rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10">
            {{ __('Retour') }}
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 rounded-2xl border border-white/10 bg-[var(--frs-card)] p-5">
            <div class="font-extrabold tracking-wide mb-3">{{ __('Client') }}</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                    <div class="text-white/60">{{ __('Nom') }}</div>
                    <div class="font-bold mt-1">{{ $client?->prenom }} {{ $client?->nom }}</div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                    <div class="text-white/60">{{ __('Email') }}</div>
                    <div class="font-bold mt-1">{{ $client?->email }}</div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4 md:col-span-2">
                    <div class="text-white/60">{{ __('Adresse livraison') }}</div>
                    <div class="font-bold mt-1">{{ $commande->adresse_livraison }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-5">
            <div class="font-extrabold tracking-wide mb-3">{{ __('Statut') }}</div>

            @php
                $steps = ['en_attente', 'confirmee', 'expediee', 'livree'];
                $current = (string) $commande->statut;
                $currentIndex = array_search($current, $steps, true);
            @endphp

            <div class="space-y-3">
                @foreach($steps as $i => $s)
                    @php
                        $done = $currentIndex !== false && $i <= $currentIndex;
                        $isCurrent = $current === $s;
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center border"
                             style="{{ $done ? 'background: linear-gradient(135deg, var(--frs-primary), #0A3D7A); border-color: transparent;' : 'background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.10);' }}">
                            <i class="fa-solid {{ $done ? 'fa-check' : 'fa-circle' }} text-white text-xs"></i>
                        </div>
                        <div class="{{ $isCurrent ? 'font-extrabold' : 'font-semibold text-white/80' }}">
                            {{ match($s) { 'en_attente' => __('En attente'), 'confirmee' => __('Confirmée'), 'expediee' => __('Expédiée'), 'livree' => __('Livrée'), default => $s } }}
                        </div>
                    </div>
                @endforeach

                @if($current === 'annulee')
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center border border-red-400/20 bg-red-500/10">
                            <i class="fa-solid fa-xmark text-red-300 text-xs"></i>
                        </div>
                        <div class="font-extrabold text-red-200">{{ __('Annulée') }}</div>
                    </div>
                @endif
            </div>

            <div class="mt-5 flex items-center justify-between">
                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ (int)$commande->synced_pme === 1 ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-400/20' : 'bg-amber-500/15 text-amber-300 border border-amber-400/20' }}">
                    {{ (int)$commande->synced_pme === 1 ? __('Synchronisé PME') : __('En attente sync') }}
                </span>
                @php
                    $sumLignes = (float) $lignes->sum('sous_total');
                    $sousTotal = (float) ($commande->sous_total ?? 0);
                    if ($sousTotal <= 0 && $sumLignes > 0) {
                        $sousTotal = $sumLignes;
                    }
                    $frais = (float) ($commande->frais_livraison ?? 0);
                @endphp
                <span class="text-sm font-extrabold">{{ number_format((float)$commande->montant_total, 2, '.', ' ') }}</span>
            </div>
            <div class="mt-3 space-y-1 text-xs text-white/70">
                <div class="flex items-center justify-between">
                    <span>{{ __('Sous-total') }}</span>
                    <span class="font-extrabold">{{ number_format($sousTotal, 2, '.', ' ') }}</span>
                </div>
                @if($frais > 0)
                    <div class="flex items-center justify-between">
                        <span>{{ __('Frais livraison') }}</span>
                        <span class="font-extrabold">{{ number_format($frais, 2, '.', ' ') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Motif') }}</span>
                        <span class="font-extrabold">{{ __('Wilaya') }} {{ (int)($commande->id_wilaya ?? 0) }} - {{ $commande->wilaya_nom ?? '—' }}</span>
                    </div>
                @endif
            </div>

            <form method="POST" action="{{ url('/fournisseur/commandes/'.$commande->id.'/statut') }}" class="mt-5">
                @csrf
                @method('PUT')
                <label class="block text-sm font-semibold text-white/70 mb-2">{{ __('Changer statut') }}</label>
                <div class="flex gap-2">
                    <select name="statut"
                            class="flex-1 rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                        @foreach($statuts as $s)
                            <option value="{{ $s }}" @selected($current === $s)>{{ match($s) { 'en_attente' => __('En attente'), 'confirmee' => __('Confirmée'), 'expediee' => __('Expédiée'), 'livree' => __('Livrée'), 'annulee' => __('Annulée'), default => $s } }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="rounded-2xl px-4 py-3 font-extrabold text-white"
                            style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                        {{ __('Valider') }}
                    </button>
                </div>
                <div class="mt-2 text-xs text-white/50">{{ __('Une notification client sera créée lors du changement.') }}</div>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4">
            <div class="font-extrabold tracking-wide">{{ __('Produits commandés') }}</div>
            <div class="text-sm text-white/60">{{ $lignes->count() }} {{ __('lignes') }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-white/60">
                    <tr>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Produit') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Qté') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Prix unit') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Sous-total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @foreach($lignes as $l)
                        <tr class="hover:bg-white/5">
                            <td class="py-3 px-4">
                                <div class="font-semibold">{{ $l->produit_designation ?? __('Produit') }}</div>
                                <div class="text-xs text-white/60">{{ $l->produit_reference }}</div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if((string)$commande->statut !== 'annulee')
                                    <form method="POST" action="{{ url('/fournisseur/commandes/'.$commande->id.'/lignes/'.$l->id) }}" class="flex items-center justify-end gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="number"
                                               name="quantite"
                                               min="1"
                                               value="{{ (int)$l->quantite }}"
                                               class="w-20 text-right rounded-xl border border-white/10 bg-black/20 px-3 py-2 outline-none focus:border-[var(--frs-primary)]">
                                        <button type="submit"
                                                class="rounded-xl px-3 py-2 text-xs font-extrabold text-white"
                                                style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                                            {{ __('Valider') }}
                                        </button>
                                    </form>
                                @else
                                    <div class="font-extrabold">{{ (int)$l->quantite }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold">{{ number_format((float)$l->prix_unitaire, 2, '.', ' ') }}</td>
                            <td class="py-3 px-4 text-right font-extrabold">{{ number_format((float)$l->sous_total, 2, '.', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-white/10">
                        <td colspan="3" class="table-align-end py-3 px-4 text-right font-extrabold">{{ __('Sous-total') }}</td>
                        <td class="table-align-end py-3 px-4 text-right font-extrabold">{{ number_format($sousTotal, 2, '.', ' ') }}</td>
                    </tr>
                    @if($frais > 0)
                        <tr class="border-t border-white/10">
                            <td colspan="3" class="table-align-end py-3 px-4 text-right font-extrabold">{{ __('Frais de livraison') }}</td>
                            <td class="table-align-end py-3 px-4 text-right font-extrabold">{{ number_format($frais, 2, '.', ' ') }}</td>
                        </tr>
                        <tr class="border-t border-white/10">
                            <td colspan="3" class="table-align-end py-3 px-4 text-right font-extrabold">{{ __('Motif') }}</td>
                            <td class="table-align-end py-3 px-4 text-right font-extrabold">{{ __('Wilaya') }} {{ (int)($commande->id_wilaya ?? 0) }} - {{ $commande->wilaya_nom ?? '—' }}</td>
                        </tr>
                    @endif
                    <tr class="border-t border-white/10">
                        <td colspan="3" class="table-align-end py-4 px-4 text-right font-extrabold">{{ __('Total') }}</td>
                        <td class="table-align-end py-4 px-4 text-right font-extrabold">{{ number_format((float)$commande->montant_total, 2, '.', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
