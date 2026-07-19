@extends('store.layout')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <section class="soft-card rounded-[28px] p-5 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.2em] text-emerald-700">{{ __('Historique') }}</div>
            <div class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Mes commandes') }}</div>
            <div class="mt-2 text-sm text-slate-600">{{ __('Suivez votre historique et l’état de chaque commande.') }}</div>
        </div>
        <a href="{{ url('/') }}" class="interactive-lift inline-flex items-center gap-2 rounded-2xl border border-white/70 bg-white px-4 py-2.5 text-sm text-slate-500 shadow-sm hover:text-slate-900">
            <i class="fa-solid fa-store"></i>
            {{ __('Retour store') }}
        </a>
    </div>
    </section>

    <div class="soft-card rounded-[28px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-slate-500">
                    <tr>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">#</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Date') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Boutique') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Statut') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Total') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($commandes as $c)
                        @php
                            $statut = (string)$c->statut;
                            $badge = match($statut) {
                                'en_attente' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                'confirmee' => 'bg-sky-50 text-sky-700 border border-sky-200',
                                'expediee' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                                'livree' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                'annulee' => 'bg-red-50 text-red-700 border border-red-200',
                                default => 'bg-slate-50 text-slate-600 border border-slate-200'
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
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-semibold">#{{ $c->id }}</td>
                            <td class="py-3 px-4 text-slate-700 force-ltr">{{ \Illuminate\Support\Carbon::parse($c->date_cmd)->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-4 text-slate-700">{{ $c->frs_nom ?? '—' }}</td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $badge }}">{{ $statutLabel }}</span>
                            </td>
                            <td class="table-align-end py-3 px-4 text-right font-bold force-ltr">{{ number_format((float)$c->montant_total, 2, '.', ' ') }} DA</td>
                            <td class="table-align-end py-3 px-4 text-right">
                                <a href="{{ url('/mes-commandes/'.$c->id) }}"
                                   class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2 text-xs font-extrabold text-white store-gradient shadow-sm">
                                    {{ __('Détail') }}
                                    <i class="fa-solid fa-arrow-right-long"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-600">{{ __('Aucune commande') }}</td>
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
