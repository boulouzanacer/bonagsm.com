@extends('layouts.fournisseur')

@section('content')
<div class="max-w-3xl space-y-4">
    @if($errors->any())
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200">
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-6">
        <div class="flex items-center justify-between gap-3">
            <div class="text-2xl font-extrabold tracking-wide">{{ __('Modifier Utilisateur') }}</div>
            <a href="{{ url('/fournisseur/utilisateurs/'.$user->id) }}" class="text-sm text-white/60 hover:text-white">
                {{ __('Retour') }}
            </a>
        </div>

        <form method="POST" action="{{ url('/fournisseur/utilisateurs/'.$user->id) }}" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Nom') }}</label>
                    <input name="nom"
                           value="{{ old('nom', $user->nom) }}"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Email') }}</label>
                    <input name="email"
                           type="email"
                           value="{{ old('email', $user->email) }}"
                           class="force-ltr w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Rôle') }}</label>
                    <select name="role"
                            class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                            required>
                        <option value="user" @selected(old('role', (string)$user->role) === 'user')>{{ __('user') }}</option>
                        <option value="admin" @selected(old('role', (string)$user->role) === 'admin')>{{ __('admin') }}</option>
                    </select>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center gap-3 cursor-pointer select-none mt-6">
                        <input type="checkbox"
                               name="actif"
                               value="1"
                               class="h-5 w-5 rounded border-white/20 bg-black/20"
                               @checked((int)old('actif', $user->actif ?? 0) === 1)>
                        <span class="text-sm font-semibold text-white/70">{{ __('Actif') }}</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Nouveau mot de passe (optionnel)') }}</label>
                    <input name="password"
                           type="password"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Confirmation') }}</label>
                    <input name="password_confirmation"
                           type="password"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="rounded-2xl px-6 py-3 font-extrabold text-white"
                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    {{ __('Enregistrer') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
