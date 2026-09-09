@extends('layouts.fournisseur')

@section('content')
<div class="space-y-4">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-2 mb-2">
        @php
            $tabs = [
                ['key' => 'all', 'label' => __('Tous'), 'count' => $totalCount ?? 0],
                ['key' => 'pending', 'label' => __("En attente d'approbation"), 'count' => $pendingCount ?? 0],
                ['key' => 'approved', 'label' => __('Approuvés'), 'count' => $approvedCount ?? 0],
            ];
        @endphp
        @foreach($tabs as $tab)
            @php
                $params = ['status' => $tab['key']];
                if (!empty($q)) { $params['q'] = $q; }
                $isActive = ($status ?? 'all') === $tab['key'];
            @endphp
            <a href="{{ url('/fournisseur/clients?'.http_build_query($params)) }}"
               class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold border transition-colors {{ $isActive
                   ? 'bg-[var(--frs-primary)]/20 border-[var(--frs-primary)]/40 text-white'
                   : 'bg-[var(--frs-card)] border-white/10 text-white/70 hover:bg-white/5' }}">
                <span>{{ $tab['label'] }}</span>
                @if($tab['count'] > 0)
                    <span class="inline-flex items-center justify-center min-w-7 h-7 px-2 rounded-full text-xs font-extrabold {{ $isActive
                        ? 'bg-[var(--frs-primary)] text-white'
                        : (($tab['key'] === 'pending' && $tab['count'] > 0)
                            ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30'
                            : 'bg-white/10 text-white/80') }}">
                        {{ (int)$tab['count'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ url('/fournisseur/clients') }}" class="flex flex-col md:flex-row md:items-center gap-3">
        @if(!empty($status) && $status !== 'all')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <div class="relative w-full md:max-w-md">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/50"></i>
            <input name="q"
                   value="{{ $q }}"
                   placeholder="{{ __('Rechercher nom ou code client...') }}"
                   class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] pl-11 pr-4 py-3 outline-none focus:border-[var(--frs-primary)]">
        </div>
        <button class="rounded-2xl px-4 py-3 font-bold text-white"
                style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
            {{ __('Filtrer') }}
        </button>
        <a href="{{ url('/fournisseur/clients') }}"
           class="rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10">
            {{ __('Réinitialiser') }}
        </a>
    </form>

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-white/60">
                    <tr>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Statut') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Code') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Nom') }}</th>
                        <th class="text-center py-3 px-4 font-semibold">{{ __('Tarif') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Email') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Téléphone') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Commune') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Nb commandes') }}</th>
                        <th class="text-center py-3 px-4 font-semibold">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($clients as $c)
                        @php
                            $isPending = (int)($c->approved_by_admin ?? 1) !== 1;
                        @endphp
                        <tr class="hover:bg-white/5 {{ $isPending ? 'bg-amber-500/5' : '' }}"
                            onclick="window.location='{{ url('/fournisseur/clients/'.$c->id) }}'">
                            <td class="py-3 px-4" onclick="event.stopPropagation()">
                                @if($isPending)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-amber-500/15 text-amber-300 border border-amber-400/20">
                                        <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                                        {{ __("En attente") }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/15 text-emerald-300 border border-emerald-400/20">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        {{ __("Approuvé") }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-semibold force-ltr">{{ $c->code_client ?? '-' }}</td>
                            <td class="py-3 px-4 text-white/80">{{ $c->prenom }} {{ $c->nom }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-9 px-3 py-1 rounded-full text-xs font-extrabold bg-white/10 border border-white/10">
                                    <span class="force-ltr">{{ (int)($c->tarif ?? 1) }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-white/80 force-ltr">{{ $c->email }}</td>
                            <td class="py-3 px-4 text-white/80 force-ltr">{{ $c->telephone }}</td>
                            <td class="py-3 px-4 text-white/80">{{ $c->commune_nom ?? '-' }}</td>
                            <td class="table-align-end py-3 px-4 text-right font-extrabold force-ltr">{{ (int)$c->nb_commandes }}</td>
                            <td class="py-3 px-4" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-center gap-2">
                                    @if($isPending)
                                        <form method="POST" action="{{ url('/fournisseur/clients/'.$c->id.'/approve') }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ __("Approuver") }}"
                                                    class="rounded-xl px-3 py-2 text-xs font-extrabold text-white inline-flex items-center gap-1.5"
                                                    style="background: linear-gradient(135deg, #10b981, #059669);">
                                                <i class="fa-solid fa-check"></i>
                                                <span class="hidden sm:inline">{{ __("Approuver") }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ url('/fournisseur/clients/'.$c->id.'/reject') }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ __("Désapprouver") }}"
                                                    class="rounded-xl px-3 py-2 text-xs font-extrabold border border-red-400/30 text-red-300 hover:bg-red-500/10 inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-xmark"></i>
                                                <span class="hidden sm:inline">{{ __("Désapprouver") }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-white/60">
                                @if(($status ?? 'all') === 'pending')
                                    {{ __("Aucun client en attente d'approbation.") }}
                                @elseif(($status ?? 'all') === 'approved')
                                    {{ __("Aucun client approuvé.") }}
                                @else
                                    {{ __('Aucun client') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $clients->links() }}
    </div>
</div>
@endsection
