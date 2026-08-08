@extends('layouts.fournisseur')

@section('content')
<div class="max-w-4xl space-y-4">
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

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-6">
        <div class="text-2xl font-extrabold tracking-wide">{{ __('Paramètres Site Web') }}</div>

        <form method="POST" action="{{ url('/fournisseur/parametres-site') }}" class="mt-5" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Logo du site (optionnel)') }}</label>
                    <input type="file"
                           name="logo"
                           accept="image/png,image/jpeg,image/webp"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                    <div class="mt-2 text-xs text-white/60">
                        {{ __('Dimensions idéales: 512×512 (carré). Minimum: 256×256. Format conseillé: PNG/WebP (fond transparent).') }}
                    </div>
                    @if(($frs->logo_path ?? null))
                        <div class="mt-3 flex items-center gap-4">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($frs->logo_path) }}"
                                 alt=""
                                 class="h-24 w-24 rounded-2xl object-cover border border-white/10 bg-white">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox"
                                       name="remove_logo"
                                       value="1"
                                       class="h-5 w-5 rounded border-white/20 bg-black/20">
                                <span class="text-sm font-semibold text-white/70">{{ __('Supprimer') }}</span>
                            </label>
                        </div>
                    @endif
                </div>

                <div>
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox"
                               name="show_prices_to_guests"
                               value="1"
                               class="h-5 w-5 rounded border-white/20 bg-black/20"
                               @checked((int)old('show_prices_to_guests', $frs->show_prices_to_guests ?? 1) === 1)>
                        <span class="text-sm font-semibold text-white/70">{{ __('Afficher les prix aux invités (non connectés)') }}</span>
                    </label>
                </div>

                <div class="space-y-3">
                    <div class="text-sm font-extrabold tracking-wide text-white/80 pt-2">{{ __('Options Stock') }}</div>

                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox"
                               name="show_stock"
                               value="1"
                               class="h-5 w-5 rounded border-white/20 bg-black/20"
                               @checked((int)old('show_stock', $frs->show_stock ?? 1) === 1)>
                        <span class="text-sm font-semibold text-white/70">{{ __('Afficher le stock sur la boutique') }}</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox"
                               name="allow_out_of_stock_orders"
                               value="1"
                               class="h-5 w-5 rounded border-white/20 bg-black/20"
                               @checked((int)old('allow_out_of_stock_orders', $frs->allow_out_of_stock_orders ?? 0) === 1)>
                        <span class="text-sm font-semibold text-white/70">{{ __('Autoriser les commandes en rupture de stock') }}</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox"
                               name="show_null_stock"
                               value="1"
                               class="h-5 w-5 rounded border-white/20 bg-black/20"
                               @checked((int)old('show_null_stock', $frs->show_null_stock ?? 1) === 1)>
                        <span class="text-sm font-semibold text-white/70">{{ __('Afficher les produits avec stock null') }}</span>
                    </label>
                </div>

                <div class="pt-2">
                    <div class="text-sm font-extrabold tracking-wide text-white/80">{{ __('Pixels') }}</div>
                    <div class="text-xs text-white/60 mt-1">{{ __('Meta Pixel et TikTok Pixel pour le suivi.') }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Meta Pixel ID') }}</label>
                        <input name="meta_pixel_id"
                               value="{{ old('meta_pixel_id', $frs->meta_pixel_id ?? '') }}"
                               inputmode="numeric"
                               class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                               placeholder="{{ __('ex: 123456789012345') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('TikTok Pixel ID') }}</label>
                        <input name="tiktok_pixel_id"
                               value="{{ old('tiktok_pixel_id', $frs->tiktok_pixel_id ?? '') }}"
                               class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                               placeholder="{{ __('ex: C3ABCDEFG12345') }}">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="rounded-2xl px-6 py-3 font-extrabold text-white"
                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    {{ __('Enregistrer') }}
                </button>
            </div>
        </form>
    </div>

    <div id="database-tools" class="rounded-[24px] p-5 border border-red-400/20 bg-gradient-to-br from-red-500/10 via-transparent to-amber-500/10">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center"
                         style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                        <i class="fa-solid fa-database text-white text-lg"></i>
                    </div>
                    <div>
                        <div class="text-lg font-extrabold tracking-wide">{{ __('Base de données') }}</div>
                        <div class="mt-1 text-sm text-white/70">
                            {{ __("Entrez votre mot de passe pour déverrouiller la sélection des tables, puis cochez celles à réinitialiser.") }}
                        </div>
                    </div>
                </div>
                <div class="mt-4 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    {{ __("Attention : cette action supprime définitivement les données du fournisseur connecté dans les tables sélectionnées.") }}
                </div>
            </div>

            <form method="POST"
                  action="{{ url('/fournisseur/parametres-site/database/unlock') }}"
                  class="w-full max-w-xl rounded-2xl border border-white/10 bg-black/20 p-4">
                @csrf
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label for="database_password" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.2em] text-white/50">
                            {{ __('Mot de passe') }}
                        </label>
                        <input id="database_password"
                               name="database_password"
                               type="password"
                               autocomplete="current-password"
                               class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 outline-none transition focus:border-red-400/40 focus:ring-4 focus:ring-red-500/10"
                               placeholder="{{ __('Entrez votre mot de passe') }}">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 font-extrabold text-white"
                            style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                        <i class="fa-solid fa-check"></i>
                        <span>{{ __('OK') }}</span>
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 font-bold {{ $database_tools_unlocked ? 'border-emerald-400/20 bg-emerald-500/15 text-emerald-300' : 'border-amber-400/20 bg-amber-500/15 text-amber-300' }}">
                        {{ $database_tools_unlocked ? __('Accès déverrouillé pendant 15 minutes') : __('Accès verrouillé') }}
                    </span>
                    <span class="text-white/60">{{ __('Seules les données du fournisseur courant sont concernées.') }}</span>
                </div>
            </form>
        </div>

        <form method="POST"
              action="{{ url('/fournisseur/parametres-site/database/reset') }}"
              class="mt-5"
              onsubmit="return confirmDatabaseReset(this);">
            @csrf
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                @foreach($database_tables as $tableKey => $table)
                    <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-[var(--frs-card)] p-4 {{ !$database_tools_unlocked ? 'opacity-60' : '' }}">
                        <input type="checkbox"
                               name="tables[]"
                               value="{{ $tableKey }}"
                               data-label="{{ $table['label'] }}"
                               class="mt-1 h-4 w-4 rounded border-white/20 bg-transparent text-red-500 focus:ring-red-500/20"
                               @checked(in_array($tableKey, old('tables', []), true))
                               @disabled(!$database_tools_unlocked)>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-extrabold text-white">{{ $table['label'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[11px] font-bold text-white/70">
                                    {{ $table['count'] }} {{ __('ligne(s)') }}
                                </span>
                            </div>
                            <div class="mt-1 text-sm text-white/80">{{ $table['title'] }}</div>
                            <div class="mt-1 text-xs text-white/60">{{ $table['description'] }}</div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-white/60">
                    {{ __("Conseil : si une table dépend de cmd1 ou produit, commencez par vider cmd1.") }}
                </div>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-50"
                        style="background: linear-gradient(135deg, #dc2626, #991b1b);"
                        @disabled(!$database_tools_unlocked)>
                    <i class="fa-solid fa-trash"></i>
                    <span>{{ __('Réinitialiser les tables cochées') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDatabaseReset(form) {
        const checked = Array.from(form.querySelectorAll('input[name="tables[]"]:checked'));
        if (checked.length === 0) {
            alert('Sélectionnez au moins une table.');
            return false;
        }

        const labels = checked.map((input) => input.dataset.label || input.value);
        return window.confirm('Confirmer la suppression complète des données pour : ' + labels.join(', ') + ' ?');
    }
</script>
@endsection
