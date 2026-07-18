@extends('layouts.app')

@section('content')
<div class="relative min-h-screen overflow-hidden flex items-center justify-center px-4 bg-gradient-to-br from-slate-100 via-emerald-50 to-slate-200 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
    <div class="absolute -top-20 left-0 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
    <div class="absolute right-0 bottom-0 h-80 w-80 rounded-full bg-blue-500/10 blur-3xl"></div>
    <div class="relative w-full max-w-5xl grid gap-6 lg:grid-cols-[1.05fr,0.95fr] items-center">
        <div class="hidden lg:block text-slate-900 dark:text-white">
            <div class="inline-flex items-center rounded-full border border-emerald-200/70 bg-white/70 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.25em] text-emerald-700 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-emerald-300">
                BonaGsm Admin
            </div>
            <h1 class="mt-6 text-5xl font-extrabold leading-tight">
                Gérez votre catalogue avec une interface plus moderne.
            </h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">
                Connectez-vous pour piloter produits, commandes, clients et paramètres depuis un espace fournisseur clair, responsive et confortable sur tout écran.
            </p>
            <div class="mt-8 grid grid-cols-3 gap-3 max-w-xl">
                <div class="rounded-3xl border border-white/60 bg-white/75 p-4 shadow-xl shadow-slate-200/60 dark:border-white/10 dark:bg-white/5 dark:shadow-none">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Produits</div>
                    <div class="mt-2 text-2xl font-extrabold">Smart</div>
                </div>
                <div class="rounded-3xl border border-white/60 bg-white/75 p-4 shadow-xl shadow-slate-200/60 dark:border-white/10 dark:bg-white/5 dark:shadow-none">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Commandes</div>
                    <div class="mt-2 text-2xl font-extrabold">Fast</div>
                </div>
                <div class="rounded-3xl border border-white/60 bg-white/75 p-4 shadow-xl shadow-slate-200/60 dark:border-white/10 dark:bg-white/5 dark:shadow-none">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Responsive</div>
                    <div class="mt-2 text-2xl font-extrabold">100%</div>
                </div>
            </div>
        </div>

        <div class="w-full max-w-md lg:max-w-none mx-auto">
            <div class="text-center mb-6 text-slate-900 dark:text-white">
                <div class="text-3xl font-extrabold tracking-wide">Bona GSM</div>
                <div class="text-sm opacity-80 mt-1">Espace Fournisseur</div>
            </div>

        <div class="rounded-[32px] p-8 shadow-2xl bg-white/90 backdrop-blur border border-white/70 dark:bg-slate-900/90 dark:border-slate-800">
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ url('/fournisseur/login') }}" class="space-y-4" x-data="{ show: false }">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">Email</label>
                    <input name="email"
                           type="email"
                           value="{{ old('email') }}"
                           required
                           autocomplete="email"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-emerald-900/30" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">Password</label>
                    <div class="relative">
                        <input name="password"
                               :type="show ? 'text' : 'password'"
                               required
                               autocomplete="current-password"
                               class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 pr-12 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-emerald-900/30" />
                        <button type="button"
                                class="absolute inset-y-0 right-0 px-4 text-slate-500 hover:text-slate-700 dark:text-slate-300 dark:hover:text-slate-100"
                                @click="show = !show">
                            <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full rounded-2xl py-3.5 font-bold text-white shadow-lg transition hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #0f7a43 0%, #0b5e33 55%, #111827 100%);">
                    Se connecter
                </button>
            </form>
            <div class="mt-5 text-center text-xs text-slate-500 dark:text-slate-400">
                Tableau de bord sécurisé, moderne et optimisé mobile.
            </div>
        </div>
    </div>
</div>
@endsection
