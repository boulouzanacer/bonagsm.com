@extends('store.layout')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <section class="soft-card rounded-[28px] p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.2em] text-emerald-700">Panier</div>
            <div class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">Vérifiez vos produits avant validation</div>
            <div class="mt-2 text-sm text-slate-600">
                @if($boutique)
                    Boutique: <span class="font-semibold text-slate-900">{{ $boutique->nom_frs }}</span>
                @else
                    —
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url('/') }}"
               class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold border border-white/70 bg-white hover:bg-slate-50 shadow-sm">
                <i class="fa-solid fa-store text-[var(--store-primary)]"></i>
                Continuer
            </a>
            @if(count($items) > 0)
                <form method="POST" action="{{ url('/panier/clear') }}">
                    @csrf
                    <button type="submit"
                            class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 shadow-sm">
                        <i class="fa-solid fa-trash-can"></i>
                        Vider
                    </button>
                </form>
            @endif
        </div>
        </div>
    </section>

    @if(count($items) === 0)
        <div class="soft-card rounded-[28px] p-10 text-center text-slate-600">
            Votre panier est vide.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            <div class="lg:col-span-2 space-y-3">
                @foreach($items as $it)
                    @php($p = $it['produit'])
                    <div class="soft-card interactive-lift rounded-[28px] p-3 sm:p-5">
                        <div class="flex items-start gap-3 sm:gap-4">
                            <a href="{{ url('/produits/'.$p->id) }}" class="h-20 w-20 rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 flex-shrink-0 sm:h-20 sm:w-28">
                                @if(($it['image'] ?? '') !== '')
                                    <img src="{{ $it['image'] }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                                        <i class="fa-regular fa-image"></i>
                                    </div>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2">
                                    <a href="{{ url('/produits/'.$p->id) }}" class="block text-sm sm:text-base font-extrabold leading-tight text-slate-900 hover:text-[var(--store-primary)]">
                                        {{ $p->designation }}
                                    </a>
                                    <div class="text-xs sm:text-sm text-slate-500">Ref: {{ $p->reference }}</div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                                            Stock: {{ (int)$p->stock }}
                                        </span>
                                        @if(($can_show_prices ?? false) || ($client ?? null))
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                                {{ number_format((float)$it['prix_unitaire'], 2, '.', ' ') }} DA / u
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                                                Connexion requise
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-col gap-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <form method="POST" action="{{ url('/panier/update') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    @csrf
                                    <input type="hidden" name="produit_id" value="{{ $p->id }}">
                                    <div class="inline-flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm sm:w-auto sm:justify-start">
                                        <span class="mr-3 text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Qté</span>
                                        <input type="number"
                                               name="qty"
                                               min="1"
                                               max="{{ max(1, (int)$p->stock) }}"
                                               value="{{ (int)$it['qty'] }}"
                                               class="w-20 bg-transparent text-right sm:text-left text-base font-bold outline-none focus:border-[var(--store-primary)]">
                                    </div>
                                    <button type="submit"
                                            class="interactive-lift w-full rounded-2xl px-4 py-2.5 text-sm font-bold border border-slate-200 bg-white hover:bg-slate-50 shadow-sm sm:w-auto">
                                        Mettre à jour
                                    </button>
                                </form>

                                <form method="POST" action="{{ url('/panier/remove') }}">
                                @csrf
                                <input type="hidden" name="produit_id" value="{{ $p->id }}">
                                    <button type="submit"
                                            aria-label="Supprimer"
                                            class="interactive-lift inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 shadow-sm">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Total ligne</div>
                                    @if(($can_show_prices ?? false) || ($client ?? null))
                                        <div class="text-lg font-extrabold text-slate-900">{{ number_format((float)$it['line_total'], 2, '.', ' ') }} DA</div>
                                    @else
                                        <div class="text-sm font-extrabold text-slate-500">—</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="soft-card rounded-[28px] p-6 h-fit lg:sticky lg:top-24">
                <div class="text-lg font-extrabold tracking-wide">Récapitulatif</div>
                <div class="mt-2 text-sm text-slate-500">Validez vos quantités et passez à l’étape suivante.</div>
                <div class="mt-5 flex items-center justify-between text-slate-600">
                    <span>Total</span>
                    @if(($can_show_prices ?? false) || ($client ?? null))
                        <span class="font-extrabold text-slate-900 text-xl">{{ number_format((float)$total, 2, '.', ' ') }} DA</span>
                    @else
                        <span class="font-extrabold text-slate-500">Connectez-vous</span>
                    @endif
                </div>

                <div class="mt-6">
                    <a href="{{ url('/checkout') }}"
                       class="interactive-lift w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3.5 text-sm font-extrabold text-white store-gradient shadow-lg shadow-emerald-950/15"
                       >
                        <i class="fa-solid fa-lock"></i>
                        Commander
                    </a>
                </div>

                <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
                    Les commandes sont créées pour une seule boutique à la fois.
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
