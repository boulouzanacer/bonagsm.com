@extends('store.layout')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="rounded-2xl border border-slate-200 bg-[var(--store-card)] p-6">
        <div class="text-2xl font-extrabold tracking-wide">{{ __('Connexion') }}</div>
        <div class="mt-1 text-sm text-slate-600">{{ __('Accédez au store et passez vos commandes.') }}</div>

        <form method="POST" action="{{ url('/login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Email') }}</label>
                <input name="email"
                       value="{{ old('email') }}"
                       type="email"
                       class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]"
                       required>
                @error('email')
                    <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Mot de passe') }}</label>
                <input name="password"
                       type="password"
                       class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[var(--store-primary)]"
                       required>
                @error('password')
                    <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-extrabold text-white"
                    style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);">
                <i class="fa-solid fa-right-to-bracket"></i>
                {{ __('Se connecter') }}
            </button>
        </form>

        <div class="mt-4 text-sm text-slate-600">
            {{ __('Pas de compte ?') }}
            <a href="{{ url('/register') }}" class="text-[var(--store-primary)] font-bold hover:underline">{{ __('Créer un compte') }}</a>
        </div>
    </div>
</div>
@endsection
