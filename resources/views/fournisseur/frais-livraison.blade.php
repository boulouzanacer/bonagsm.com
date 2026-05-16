@extends('layouts.fournisseur')

@section('content')
@php
    $canEdit = (string)session('role', '') === 'fournisseur' || (int)session('is_admin', 0) === 1;
@endphp
<div class="space-y-4" x-data="{ enabled: @json($enabled), canEdit: @json($canEdit) }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('/fournisseur/frais-livraison') }}" class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        @csrf
        @method('PUT')

        <div class="p-5 flex items-center justify-between gap-3 border-b border-white/10">
            <div>
                <div class="text-lg font-extrabold tracking-wide">Frais de livraison</div>
                <div class="text-sm text-white/60">Définir des frais par wilaya.</div>
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="hidden" name="enabled" :value="enabled ? 1 : 0">
                    <input type="checkbox"
                           class="sr-only peer"
                           x-model="enabled"
                           :disabled="!canEdit">
                    <div class="w-12 h-7 rounded-full bg-white/15 peer-checked:bg-[var(--frs-primary)] transition relative"
                         :class="canEdit ? '' : 'opacity-60'">
                        <div class="absolute left-1 top-1 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></div>
                    </div>
                    <span class="text-sm font-extrabold text-white/80" x-text="enabled ? 'Activé' : 'Désactivé'"></span>
                </label>

                @if($canEdit)
                    <button type="submit"
                            class="rounded-2xl px-4 py-3 font-extrabold text-white"
                            style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                        Enregistrer
                    </button>
                @else
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-white/10 border border-white/10 text-white/70">
                        Lecture seule
                    </span>
                @endif
            </div>
        </div>

        <div class="p-5" x-show="enabled" x-transition>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-white/60">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold">Wilaya</th>
                            <th class="text-right py-3 px-4 font-semibold">Frais (DA)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach($wilayas as $w)
                            @php($fee = (float)($fees->get((int)$w->ID_WILAYA)?->frais ?? 0))
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="font-bold">{{ $w->ID_WILAYA }} - {{ $w->WILAYA }}</div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <input name="fees[{{ $w->ID_WILAYA }}]"
                                           type="number"
                                           step="0.01"
                                           min="0"
                                           value="{{ old('fees.'.$w->ID_WILAYA, $fee) }}"
                                           class="w-40 text-right rounded-2xl border border-white/10 bg-black/20 px-4 py-2 outline-none focus:border-[var(--frs-primary)]"
                                           {{ $canEdit ? '' : 'disabled' }}>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
@endsection
