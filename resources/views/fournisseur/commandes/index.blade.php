@extends('layouts.fournisseur')

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ url('/fournisseur/commandes') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
            <select name="statut"
                    class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                <option value="">{{ __('Tous statuts') }}</option>
                @foreach($statuts as $s)
                    <option value="{{ $s }}" @selected((string)$selected_statut === (string)$s)>{{ match($s) { 'en_attente' => __('En attente'), 'confirmee' => __('Confirmée'), 'expediee' => __('Expédiée'), 'livree' => __('Livrée'), 'annulee' => __('Annulée'), default => $s } }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <select name="client"
                    class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                <option value="">{{ __('Tous clients') }}</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected((string)$selected_client === (string)$c->id)>{{ $c->prenom }} {{ $c->nom }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <input type="date"
                   name="from"
                   value="{{ $from }}"
                   class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
        </div>

        <div>
            <input type="date"
                   name="to"
                   value="{{ $to }}"
                   class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
        </div>

        <button class="rounded-2xl px-4 py-3 font-bold text-white"
                style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
            {{ __('Filtrer') }}
        </button>
    </form>

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

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-white/60">
                    <tr>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('N°') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Client') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Date') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Montant') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Statut') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Synced PME') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($commandes as $c)
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
                        <tr class="hover:bg-white/5 cursor-pointer" onclick="window.location='{{ url('/fournisseur/commandes/'.$c->id) }}'">
                            <td class="py-3 px-4 font-semibold">#{{ $c->id }}</td>
                            <td class="py-3 px-4 text-white/80">{{ trim(($c->client_prenom ?? '').' '.($c->client_nom ?? '')) }}</td>
                            <td class="py-3 px-4 text-white/80 force-ltr">{{ \Illuminate\Support\Carbon::parse($c->date_cmd)->format('d/m/Y H:i') }}</td>
                            <td class="table-align-end py-3 px-4 text-right font-extrabold force-ltr">{{ number_format((float)$c->montant_total, 2, '.', ' ') }}</td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $badge }}">{{ $statutLabel }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ (int)$c->synced_pme === 1 ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-400/20' : 'bg-amber-500/15 text-amber-300 border border-amber-400/20' }}">
                                    {{ (int)$c->synced_pme === 1 ? __('Synchronisé') : __('En attente') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-white/60">{{ __('Aucune commande') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $commandes->links() }}
    </div>
</div>
@endsection
