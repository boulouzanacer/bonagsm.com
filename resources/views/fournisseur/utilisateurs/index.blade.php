@extends('layouts.fournisseur')

@section('content')
<div class="space-y-4">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <form method="GET" action="{{ url('/fournisseur/utilisateurs') }}" class="flex flex-col md:flex-row md:items-center gap-3 w-full">
            <div class="relative w-full md:max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/50"></i>
                <input name="q"
                       value="{{ $q }}"
                       placeholder="{{ __('Rechercher nom ou email...') }}"
                       class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] pl-11 pr-4 py-3 outline-none focus:border-[var(--frs-primary)]">
            </div>
            <button class="rounded-2xl px-4 py-3 font-bold text-white"
                    style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                {{ __('Filtrer') }}
            </button>
            <a href="{{ url('/fournisseur/utilisateurs') }}"
               class="rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10">
                {{ __('Réinitialiser') }}
            </a>
        </form>

        <a href="{{ url('/fournisseur/utilisateurs/create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 font-extrabold text-white"
           style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
            <i class="fa-solid fa-plus"></i>
            {{ __('Ajouter') }}
        </a>
    </div>

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-white/60">
                    <tr>
                        <th class="text-left py-3 px-4 font-semibold">#</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Nom') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Email') }}</th>
                        <th class="text-center py-3 px-4 font-semibold">{{ __('Rôle') }}</th>
                        <th class="text-center py-3 px-4 font-semibold">{{ __('Statut') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Créé le') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($users as $u)
                        @php
                            $role = (string)($u->role ?? 'user');
                            $roleBadge = $role === 'admin'
                                ? 'bg-violet-500/15 text-violet-200 border border-violet-400/20'
                                : 'bg-white/10 text-white/70 border border-white/10';
                            $active = (int)($u->actif ?? 0) === 1;
                        @endphp
                        <tr class="hover:bg-white/5">
                            <td class="py-3 px-4 font-semibold">#{{ $u->id }}</td>
                            <td class="py-3 px-4 font-semibold">{{ $u->nom }}</td>
                            <td class="py-3 px-4 text-white/80 force-ltr">{{ $u->email }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $roleBadge }}">{{ __($role) }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $active ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-400/20' : 'bg-red-500/15 text-red-300 border border-red-400/20' }}">
                                    {{ $active ? __('Actif') : __('Inactif') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-white/70">
                                {{ \Illuminate\Support\Carbon::parse($u->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ url('/fournisseur/utilisateurs/'.$u->id.'/edit') }}"
                                       class="rounded-xl px-3 py-2 text-xs font-extrabold border border-white/10 hover:bg-white/10">
                                        {{ __('Modifier') }}
                                    </a>
                                    <form method="POST" action="{{ url('/fournisseur/utilisateurs/'.$u->id) }}"
                                          onsubmit="return confirm(@js(__('Supprimer cet utilisateur ?')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-xl px-3 py-2 text-xs font-extrabold border border-red-400/20 bg-red-500/10 text-red-200 hover:bg-red-500/15">
                                            {{ __('Supprimer') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-white/60">{{ __('Aucun utilisateur') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $users->links() }}
    </div>
</div>
@endsection
