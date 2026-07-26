@extends('layouts.fournisseur')

@section('content')
<div x-data="{ show: false }" class="max-w-2xl mx-auto">
    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-8">
        <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-2xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                <i class="fa-solid fa-key text-white text-lg"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold tracking-wide">{{ __('Votre Token PME Pro') }}</div>
                <div class="text-sm text-white/60">{{ __('Ce token permet à votre logiciel PME Pro de se connecter à Bona GSM.') }}</div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 p-4">
            <div class="text-xs font-extrabold uppercase tracking-[0.2em] text-emerald-300">{{ __('Endpoint PME') }}</div>
            <div class="mt-2 font-mono text-sm break-all text-white/90 force-ltr">{{ $endpoint }}</div>
            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                <button type="button"
                        class="flex-1 rounded-2xl px-4 py-3 font-extrabold border border-white/10 hover:bg-white/10"
                        @click="navigator.clipboard.writeText('{{ $endpoint }}')">
                    {{ __('Copier endpoint') }}
                </button>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-white/10 bg-black/25 p-5">
            <div class="font-mono text-sm break-all text-white/90" x-text="show ? '{{ $token }}' : '●●●●●●●●-●●●●-●●●●-●●●●'"></div>
        </div>

        <div class="mt-4 flex flex-col sm:flex-row gap-2">
            <button type="button"
                    class="flex-1 rounded-2xl px-4 py-3 font-extrabold border border-white/10 hover:bg-white/10"
                    @click="show = !show">
                <span x-show="!show">{{ __('Afficher') }}</span>
                <span x-show="show">{{ __('Masquer') }}</span>
            </button>
            <button type="button"
                    class="flex-1 rounded-2xl px-4 py-3 font-extrabold text-white"
                    style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);"
                    @click="navigator.clipboard.writeText('{{ $token }}')">
                {{ __('Copier') }}
            </button>
        </div>

        <div class="mt-4 text-sm text-amber-200/90">
            {{ __('Ne partagez jamais ce token.') }}
        </div>

        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-5">
            <div class="text-sm font-extrabold">{{ __('Comment l’utiliser dans PMESync ?') }}</div>
            <ol class="mt-3 space-y-2 text-sm text-white/70 list-decimal pl-5 rtl-list-pad">
                <li>{{ __("Ouvrez l'application PMESync.") }}</li>
                <li>{{ __('Allez dans Paramètres de connexion.') }}</li>
                <li>{{ __("Collez l'endpoint PME dans le champ Endpoint PME.") }}</li>
                <li>{{ __('Copiez ce token dans le champ Token API PME.') }}</li>
                <li>{{ __('Choisissez le mode manuel ou automatique puis enregistrez.') }}</li>
            </ol>
        </div>
    </div>
</div>
@endsection
