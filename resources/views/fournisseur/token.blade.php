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
    </div>
</div>
@endsection
