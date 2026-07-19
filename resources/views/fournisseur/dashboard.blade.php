@extends('layouts.fournisseur')

@section('content')
<div class="mb-6 rounded-[28px] border border-white/10 bg-gradient-to-br from-emerald-500/15 via-transparent to-sky-500/10 p-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex items-center rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.2em] text-emerald-300">{{ __('BonaGsm Dashboard') }}</div>
            <p class="mt-2 max-w-2xl text-sm text-white/70">
                {{ __('Suivez vos commandes, vos clients et vos stocks.') }}
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                <div class="text-[11px] uppercase tracking-[0.2em] text-white/50">{{ __('En attente') }}</div>
                <div class="mt-2 text-2xl font-extrabold">{{ $cmd_en_attente }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                <div class="text-[11px] uppercase tracking-[0.2em] text-white/50">{{ __('Clients') }}</div>
                <div class="mt-2 text-2xl font-extrabold">{{ $clients_abonnes }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 col-span-2 sm:col-span-1">
                <div class="text-[11px] uppercase tracking-[0.2em] text-white/50">{{ __('Produits') }}</div>
                <div class="mt-2 text-2xl font-extrabold">{{ $produits_actifs }}</div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <a href="{{ url('/fournisseur/commandes?statut=en_attente') }}"
       class="rounded-[24px] p-5 border border-white/10 bg-[var(--frs-card)] hover:bg-white/5 transition hover:-translate-y-1">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-white/60">{{ __('Commandes en attente') }}</div>
                <div class="text-3xl font-extrabold mt-1">{{ $cmd_en_attente }}</div>
            </div>
            <div class="h-12 w-12 rounded-2xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, #fb923c, #f97316);">
                <i class="fa-solid fa-hourglass-half text-white text-lg"></i>
            </div>
        </div>
        <div class="mt-3 text-xs text-white/60">{{ __('Cliquez pour afficher la liste filtrée') }}</div>
    </a>

    <div class="rounded-[24px] p-5 border border-white/10 bg-[var(--frs-card)]">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-white/60">{{ __('Commandes du jour') }}</div>
                <div class="text-3xl font-extrabold mt-1">{{ $cmd_du_jour }}</div>
            </div>
            <div class="h-12 w-12 rounded-2xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                <i class="fa-solid fa-cart-shopping text-white text-lg"></i>
            </div>
        </div>
    </div>

    <div class="rounded-[24px] p-5 border border-white/10 bg-[var(--frs-card)]">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-white/60">{{ __('Clients') }}</div>
                <div class="text-3xl font-extrabold mt-1">{{ $clients_abonnes }}</div>
            </div>
            <div class="h-12 w-12 rounded-2xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                <i class="fa-solid fa-users text-white text-lg"></i>
            </div>
        </div>
    </div>

    <div class="rounded-[24px] p-5 border border-white/10 bg-[var(--frs-card)]">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-white/60">{{ __('Produits actifs') }}</div>
                <div class="text-3xl font-extrabold mt-1">{{ $produits_actifs }}</div>
            </div>
            <div class="h-12 w-12 rounded-2xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, #a855f7, #7c3aed);">
                <i class="fa-solid fa-box-open text-white text-lg"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-4">
    <div class="rounded-[24px] p-5 border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <div class="font-extrabold tracking-wide">{{ __('Dernières commandes') }}</div>
            <a href="{{ url('/fournisseur/commandes') }}" class="text-sm text-[var(--frs-primary)] hover:opacity-90">{{ __('Voir tout') }}</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-white/60">
                    <tr>
                        <th class="text-left py-3 pr-4 font-semibold">#</th>
                        <th class="table-align-start text-left py-3 pr-4 font-semibold">{{ __('Date') }}</th>
                        <th class="table-align-start text-left py-3 pr-4 font-semibold">{{ __('Client') }}</th>
                        <th class="table-align-start text-left py-3 pr-4 font-semibold">{{ __('Statut') }}</th>
                        <th class="table-align-end text-right py-3 font-semibold">{{ __('Montant') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($dernieres_commandes as $c)
                        @php
                            $statut = $c->statut;
                            $badge = match($statut) {
                                'en_attente' => 'bg-amber-500/15 text-amber-300 border border-amber-400/20',
                                'confirmee' => 'bg-sky-500/15 text-sky-300 border border-sky-400/20',
                                'expediee' => 'bg-indigo-500/15 text-indigo-300 border border-indigo-400/20',
                                'livree' => 'bg-emerald-500/15 text-emerald-300 border border-emerald-400/20',
                                'annulee' => 'bg-red-500/15 text-red-300 border border-red-400/20',
                                default => 'bg-white/10 text-white/70 border border-white/10'
                            };
                            $statutLabel = match($statut) {
                                'en_attente' => __('En attente'),
                                'confirmee' => __('Confirmée'),
                                'expediee' => __('Expédiée'),
                                'livree' => __('Livrée'),
                                'annulee' => __('Annulée'),
                                default => $statut,
                            };
                        @endphp
                        <tr class="hover:bg-white/5">
                            <td class="py-3 pr-4 font-semibold">#{{ $c->id }}</td>
                            <td class="py-3 pr-4 text-white/80">{{ \Illuminate\Support\Carbon::parse($c->date_cmd)->format('d/m/Y H:i') }}</td>
                            <td class="py-3 pr-4 text-white/80">{{ trim(($c->client_prenom ?? '').' '.($c->client_nom ?? '')) }}</td>
                            <td class="py-3 pr-4">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $badge }}">{{ $statutLabel }}</span>
                            </td>
                            <td class="table-align-end py-3 text-right font-bold">{{ number_format((float)$c->montant_total, 2, '.', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-white/60">{{ __('Aucune commande') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-[24px] p-5 border border-white/10 bg-[var(--frs-card)]">
        <div class="flex items-center justify-between mb-4">
            <div class="font-extrabold tracking-wide">{{ __('Produits en rupture de stock') }}</div>
            <a href="{{ url('/fournisseur/produits') }}" class="text-sm text-[var(--frs-primary)] hover:opacity-90">{{ __('Voir tout') }}</a>
        </div>

        <div class="space-y-3">
            @forelse($rupture_stock as $p)
                @php
                    $stock = (int) $p->stock;
                    $badge = $stock === 0
                        ? 'bg-red-500/15 text-red-300 border border-red-400/20'
                        : 'bg-amber-500/15 text-amber-300 border border-amber-400/20';
                    $label = $stock === 0 ? __('Rupture') : __('Stock faible');
                @endphp
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold truncate">{{ $p->designation }}</div>
                        <div class="text-xs text-white/60 truncate">{{ $p->reference }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $badge }}">{{ $label }} ({{ $stock }})</span>
                    </div>
                </div>
            @empty
                <div class="text-white/60">{{ __('Aucun produit en alerte stock.') }}</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
