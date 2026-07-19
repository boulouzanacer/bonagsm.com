@extends('layouts.fournisseur')

@section('content')
@php
    $canCreate = in_array((string) session('role', ''), ['fournisseur', 'frs_user'], true);
    $canImport = (string) session('role', '') === 'fournisseur' || (int) session('is_admin', 0) === 1;
    $canEdit = in_array((string) session('role', ''), ['fournisseur', 'frs_user'], true);
    $canManage = (string) session('role', '') === 'fournisseur' || (int) session('is_admin', 0) === 1;
    $returnUrl = request()->fullUrl();
@endphp
<div class="space-y-4" x-data="productImport()">
    @if(($db_error ?? null))
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200">
            {{ $db_error }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <form method="GET" action="{{ url('/fournisseur/produits') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-3 w-full" id="searchForm">
            <div class="md:col-span-2 lg:col-span-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/50"></i>
                    <input name="q"
                           id="searchInput"
                           value="{{ $q }}"
                           placeholder="{{ __('Rechercher référence/désignation...') }}"
                           class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] pl-11 pr-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>
            </div>

            <div>
                <select name="id_categorie" onchange="this.form.submit()" class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                    <option value="">{{ __('Toutes catégories') }}</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected((string)$id_categorie === (string)$c->id)>{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="id_sous_categorie" onchange="this.form.submit()" class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                    <option value="">{{ __('Toutes sous-cat.') }}</option>
                    @foreach($sousCategories as $sc)
                        <option value="{{ $sc->id }}" @selected((string)$id_sous_categorie === (string)$sc->id)>{{ $sc->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="id_marque" onchange="this.form.submit()" class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                    <option value="">{{ __('Toutes marques') }}</option>
                    @foreach($marques as $m)
                        <option value="{{ $m->id }}" @selected((string)$id_marque === (string)$m->id)>{{ $m->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <a href="{{ url('/fournisseur/produits') }}"
                   class="flex-1 inline-flex items-center justify-center rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10"
                   title="{{ __('Réinitialiser') }}">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>

        @if($canCreate || $canImport)
            <div class="flex items-center gap-2 justify-end">
                <input type="file"
                       class="hidden"
                       id="importFileInput"
                       accept=".xlsx,.xls,.csv"
                       @change="handleFileChange($event)">
                @if($canImport)
                    <button type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 font-bold border border-white/10 hover:bg-white/10"
                            @click="openImport()">
                        <i class="fa-solid fa-file-import"></i>
                        {{ __('Importer produits') }}
                    </button>
                @endif
                @if($canCreate)
                    <a href="{{ url('/fournisseur/produits/create') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 font-bold text-white"
                       style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                        <i class="fa-solid fa-plus"></i>
                        {{ __('Nouveau produit') }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-white/60">
                    <tr>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Produit') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Référence') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Catégorie') }}</th>
                        <th class="table-align-start text-left py-3 px-4 font-semibold">{{ __('Marque') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Stock') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('PV 1') }}</th>
                        <th class="text-center py-3 px-4 font-semibold">{{ __('Statut') }}</th>
                        <th class="table-align-end text-right py-3 px-4 font-semibold">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($produits as $p)
                        @php
                            $stock = (int) $p->stock;
                            $stockBadge = $stock === 0
                                ? [__('Rupture'), 'bg-red-500/15 text-red-300 border border-red-400/20']
                                : ($stock < 5
                                    ? [__('Stock faible'), 'bg-amber-500/15 text-amber-300 border border-amber-400/20']
                                    : [__('Disponible'), 'bg-sky-500/15 text-sky-200 border border-sky-400/20']);
                            $actif = (int)($p->actif ?? 0) === 1;
                        @endphp
                        <tr class="hover:bg-white/5">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-10 w-12 rounded-xl overflow-hidden border border-white/10 bg-black/20 flex-shrink-0">
                                        @php($productImage = \App\Services\ImageProduitService::publicUrl($p->image_principale ?? ''))
                                        @if($productImage !== '')
                                            <img src="{{ $productImage }}" alt="" class="h-10 w-12 object-cover">
                                        @else
                                            <div class="h-10 w-12 flex items-center justify-center text-white/40">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-extrabold truncate">{{ $p->designation }}</div>
                                        <div class="text-xs text-white/60 truncate">{{ $p->reference }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-white/80 font-semibold">{{ $p->reference }}</td>
                            <td class="py-3 px-4">
                                <div class="text-white/80">{{ $p->categorie ?: '—' }}</div>
                                @if($p->subCategory)
                                    <div class="text-[10px] text-white/50">{{ $p->subCategory->nom }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-white/80">{{ $p->brand->nom ?? 'Standard' }}</td>
                            <td class="table-align-end py-3 px-4 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <div class="font-extrabold tabular-nums">{{ $stock }}</div>
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $stockBadge[1] }}">{{ $stockBadge[0] }}</span>
                                </div>
                            </td>
                            <td class="table-align-end py-3 px-4 text-right">
                                <span class="font-extrabold tabular-nums whitespace-nowrap">{{ number_format((float)$p->pv_1, 2, '.', ' ') }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $actif ? 'bg-sky-500/15 text-sky-200 border border-sky-400/20' : 'bg-red-500/15 text-red-300 border border-red-400/20' }}">
                                    {{ $actif ? __('Actif') : __('Inactif') }}
                                </span>
                            </td>
                            <td class="table-align-end py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ url('/fournisseur/produits/'.$p->id).'?'.http_build_query(['return' => $returnUrl]) }}"
                                       title="{{ __('Détail') }}"
                                       class="h-9 w-9 inline-flex items-center justify-center rounded-xl border border-white/10 hover:bg-white/10">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @if($canEdit)
                                        <a href="{{ url('/fournisseur/produits/'.$p->id.'/edit').'?'.http_build_query(['return' => $returnUrl]) }}"
                                           title="{{ __('Modifier') }}"
                                           class="h-9 w-9 inline-flex items-center justify-center rounded-xl border border-white/10 hover:bg-white/10">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        @if($canManage)
                                            <form method="POST" action="{{ url('/fournisseur/produits/'.$p->id.'/toggle-actif') }}">
                                                @csrf
                                                <button type="submit"
                                                        title="{{ $actif ? __('Désactiver') : __('Activer') }}"
                                                        class="h-9 w-14 inline-flex items-center justify-center rounded-xl border border-white/10 hover:bg-white/10">
                                                    <span class="inline-flex items-center h-5 w-9 rounded-full {{ $actif ? 'bg-emerald-500/40' : 'bg-white/15' }} px-0.5">
                                                        <span class="h-4 w-4 rounded-full bg-white {{ $actif ? 'ml-4' : '' }}"></span>
                                                    </span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ url('/fournisseur/produits/'.$p->id) }}"
                                                  onsubmit="return confirm(@js(__('Supprimer ce produit ?')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        title="{{ __('Supprimer') }}"
                                                        class="h-9 w-9 inline-flex items-center justify-center rounded-xl border border-red-400/20 bg-red-500/10 text-red-200 hover:bg-red-500/15">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-white/60">{{ __('Aucun produit.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $produits->links() }}
    </div>

    <div x-show="importOpen" x-transition class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/60" @click="closeImport()"></div>
        <div class="absolute inset-0 flex items-start justify-center p-4 overflow-auto">
            <div class="w-full max-w-5xl rounded-2xl border border-white/10 bg-[var(--frs-card)] shadow-2xl overflow-hidden">
                <div class="p-5 border-b border-white/10 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-lg font-extrabold tracking-wide">{{ __('Importer des produits') }}</div>
                        <div class="text-xs text-white/60">{{ __('Sélectionnez un fichier XLS/XLSX/CSV, puis mappez les colonnes.') }}</div>
                    </div>
                    <button type="button"
                            class="rounded-xl px-3 py-2 text-sm font-extrabold border border-white/10 hover:bg-white/10"
                            @click="closeImport()">
                        {{ __('Fermer') }}
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <template x-if="importError">
                        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200" x-text="importError"></div>
                    </template>
                    <template x-if="importResult">
                        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
                            <div class="font-extrabold" x-text="importResult.message"></div>
                            <div class="text-sm mt-1">
                                {{ __('Créés') }}: <span class="font-extrabold" x-text="importResult.created"></span> •
                                {{ __('Mis à jour') }}: <span class="font-extrabold" x-text="importResult.updated"></span> •
                                {{ __('Ignorés') }}: <span class="font-extrabold" x-text="importResult.skipped"></span>
                            </div>
                        </div>
                    </template>

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    class="rounded-2xl px-4 py-3 font-extrabold text-white"
                                    style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);"
                                    @click="pickFile()">
                                {{ __('Choisir fichier') }}
                            </button>
                            <div class="text-sm text-white/70" x-text="fileName || @js(__('Aucun fichier sélectionné'))"></div>
                        </div>
                        <div class="text-xs text-white/60">
                            {{ __('Clé de comparaison: Référence (obligatoire)') }}
                        </div>
                    </div>

                    <template x-if="columns.length > 0">
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne Référence (obligatoire)') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.reference">
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne Désignation') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.designation">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne Catégorie') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.categorie">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne Sous-catégorie') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.sous_categorie">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne Marque') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.marque">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne Description') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.description">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne PV 1') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.pv_1">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne PV 2') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.pv_2">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne PV 3') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.pv_3">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Colonne Stock') }}</label>
                                    <select class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                                            x-model="mapping.stock">
                                        <option value="">—</option>
                                        <template x-for="c in columns" :key="c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-white/60 mb-1">{{ __('Quand le produit existe: mettre à jour') }}</label>
                                    <div class="flex flex-wrap gap-3 pt-2">
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="designation" x-model="updateFields">
                                            designation
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="description" x-model="updateFields">
                                            description
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="pv_1" x-model="updateFields">
                                            pv_1
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="pv_2" x-model="updateFields">
                                            pv_2
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="pv_3" x-model="updateFields">
                                            pv_3
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="stock" x-model="updateFields">
                                            stock
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="categorie" x-model="updateFields">
                                            categorie
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="sous_categorie" x-model="updateFields">
                                            sous_categorie
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="checkbox" class="h-4 w-4" value="marque" x-model="updateFields">
                                            marque
                                        </label>
                                    </div>
                                    <div class="mt-3 flex items-center gap-4">
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="radio" name="stockMode" value="replace" x-model="stockMode">
                                            {{ __('Stock: remplacer') }}
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-white/70">
                                            <input type="radio" name="stockMode" value="add" x-model="stockMode">
                                            {{ __('Stock: old + new') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm text-white/60">
                                    {{ __('Lignes détectées:') }} <span class="font-extrabold text-white" x-text="rows.length"></span>
                                </div>
                                <button type="button"
                                        class="rounded-2xl px-6 py-3 font-extrabold text-white disabled:opacity-50"
                                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);"
                                        :disabled="importing"
                                        @click="runImport()">
                                    <span x-text="importing ? @js(__('Import...')) : @js(__('Importer'))"></span>
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead class="text-white/60">
                                        <tr>
                                            <th class="text-left py-2 px-3">{{ __('Référence') }}</th>
                                            <th class="text-left py-2 px-3">{{ __('Désignation') }}</th>
                                            <th class="text-left py-2 px-3">{{ __('Catégorie') }}</th>
                                            <th class="text-left py-2 px-3">{{ __('Sous-Cat.') }}</th>
                                            <th class="text-left py-2 px-3">{{ __('Marque') }}</th>
                                            <th class="text-right py-2 px-3">{{ __('Stock') }}</th>
                                            <th class="text-right py-2 px-3">{{ __('PV1') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/10">
                                        <template x-for="(r, idx) in previewRows" :key="idx">
                                            <tr>
                                                <td class="py-2 px-3 font-semibold" x-text="val(r, mapping.reference)"></td>
                                                <td class="py-2 px-3" x-text="val(r, mapping.designation)"></td>
                                                <td class="py-2 px-3" x-text="val(r, mapping.categorie)"></td>
                                                <td class="py-2 px-3" x-text="val(r, mapping.sous_categorie)"></td>
                                                <td class="py-2 px-3" x-text="val(r, mapping.marque)"></td>
                                                <td class="py-2 px-3 text-right" x-text="val(r, mapping.stock)"></td>
                                                <td class="py-2 px-3 text-right" x-text="val(r, mapping.pv_1)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    function productImport() {
        const csrf = @json(csrf_token());

        function normalizeHeader(v, idx) {
            const raw = (v === null || v === undefined) ? '' : String(v);
            const trimmed = raw.trim();
            return trimmed !== '' ? trimmed : 'COL_' + (idx + 1);
        }

        function normalizeText(v) {
            const s = (v === null || v === undefined) ? '' : String(v);
            return s
                .trim()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, ' ');
        }

        function isStopRow(row) {
            if (!row || !Array.isArray(row)) return false;
            for (const cell of row) {
                const t = normalizeText(cell);
                if (t.includes('nombre de produit')) {
                    return true;
                }
                if (t.includes('nombre des produit')) {
                    return true;
                }
                if (t.includes('nombre produits')) {
                    return true;
                }
            }
            return false;
        }

        function guess(columns, candidates) {
            const lower = columns.map(c => ({ raw: c, lc: String(c).toLowerCase() }));
            for (const cand of candidates) {
                const lc = cand.toLowerCase();
                const m = lower.find(x => x.lc === lc) || lower.find(x => x.lc.includes(lc));
                if (m) return m.raw;
            }
            return '';
        }

        return {
            importOpen: false,
            importing: false,
            importError: '',
            importResult: null,
            fileName: '',
            columns: [],
            rows: [],
            previewRows: [],
            mapping: {
                reference: '',
                designation: '',
                description: '',
                pv_1: '',
                pv_2: '',
                pv_3: '',
                stock: '',
                categorie: '',
                sous_categorie: '',
                marque: '',
            },
            updateFields: ['designation', 'description', 'pv_1', 'pv_2', 'pv_3', 'stock', 'categorie', 'sous_categorie', 'marque'],
            stockMode: 'replace',

            openImport() {
                this.importError = '';
                this.importResult = null;
                this.importOpen = true;
            },
            closeImport() {
                this.importOpen = false;
            },
            pickFile() {
                const input = document.getElementById('importFileInput');
                if (input) input.click();
            },
            val(row, col) {
                if (!col) return '';
                const v = row[col];
                if (v === null || v === undefined) return '';
                return String(v);
            },
            async handleFileChange(e) {
                this.importError = '';
                this.importResult = null;
                const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                if (!file) return;
                this.fileName = file.name;

                try {
                    const ext = (file.name.split('.').pop() || '').toLowerCase();
                    const data = await file.arrayBuffer();
                    let wb;
                    if (ext === 'csv') {
                        const text = new TextDecoder().decode(new Uint8Array(data));
                        wb = XLSX.read(text, { type: 'string' });
                    } else {
                        wb = XLSX.read(data, { type: 'array' });
                    }

                    const sheetName = wb.SheetNames[0];
                    if (!sheetName) {
                        this.importError = @js(__('Fichier invalide (aucune feuille).'));
                        return;
                    }
                    const ws = wb.Sheets[sheetName];
                    const aoa = XLSX.utils.sheet_to_json(ws, { header: 1, raw: true, defval: '' });
                    if (!Array.isArray(aoa) || aoa.length < 2) {
                        this.importError = @js(__('Le fichier doit contenir une ligne d’en-têtes + au moins 1 ligne de données.'));
                        return;
                    }

                    const headers = aoa[0].map((h, idx) => normalizeHeader(h, idx));
                    const outRows = [];
                    for (let i = 1; i < aoa.length; i++) {
                        const row = aoa[i];
                        if (!row || !Array.isArray(row)) continue;
                        if (isStopRow(row)) break;
                        const obj = {};
                        for (let c = 0; c < headers.length; c++) {
                            obj[headers[c]] = row[c] ?? '';
                        }
                        const hasAny = Object.values(obj).some(v => String(v ?? '').trim() !== '');
                        if (hasAny) outRows.push(obj);
                        if (outRows.length >= 2000) break;
                    }

                    this.columns = headers;
                    this.rows = outRows;
                    this.previewRows = outRows.slice(0, 20);

                    this.mapping.reference = guess(headers, ['reference', 'ref', 'code', 'code produit', 'code_produit', 'sku', 'ean', 'barcode']);
                    if (!this.mapping.reference) this.mapping.reference = headers[0] || '';
                    this.mapping.designation = guess(headers, ['designation', 'désignation', 'name', 'produit', 'libelle', 'libellé', 'article']);
                    this.mapping.description = guess(headers, ['description', 'desc', 'déscription']);
                    this.mapping.pv_1 = guess(headers, ['pv_1', 'pv1', 'prix1', 'price1', 'prix vente', 'prix vente ttc', 'prix_vente', 'price', 'prix']);
                    this.mapping.pv_2 = guess(headers, ['pv_2', 'pv2', 'prix2', 'price2']);
                    this.mapping.pv_3 = guess(headers, ['pv_3', 'pv3', 'prix3', 'price3']);
                    this.mapping.stock = guess(headers, ['stock', 'stock ( unité )', 'stock (unité)', 'qte', 'qté', 'quantite', 'quantité', 'qty']);
                    this.mapping.categorie = guess(headers, ['categorie', 'catégorie', 'category']);
                } catch (err) {
                    this.importError = @js(__('Impossible de lire le fichier.'));
                }
            },
            async runImport() {
                this.importError = '';
                this.importResult = null;
                if (!this.mapping.reference) {
                    this.importError = @js(__('Veuillez choisir la colonne Référence.'));
                    return;
                }
                if (!Array.isArray(this.rows) || this.rows.length === 0) {
                    this.importError = @js(__('Aucune ligne à importer.'));
                    return;
                }

                this.importing = true;
                try {
                    const res = await fetch(@json(url('/fournisseur/produits/import')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            mapping: this.mapping,
                            update: this.updateFields,
                            stock_mode: this.stockMode,
                            rows: this.rows,
                        }),
                    });
                    const json = await res.json().catch(() => null);
                    if (!res.ok || !json || json.success !== true) {
                        this.importError = (json && json.message) ? json.message : @js(__('Import échoué.'));
                        return;
                    }
                    this.importResult = json;
                } catch (_) {
                    this.importError = @js(__('Erreur serveur pendant l’import.'));
                } finally {
                    this.importing = false;
                }
            }
        };
    }

    (function () {
        const form = document.getElementById('produitsFilterForm');
        const input = document.getElementById('produitsSearchInput');
        const select = document.getElementById('produitsCategorieSelect');
        if (!form || !input || !select) return;

        let t = null;
        const debounceSubmit = () => {
            if (t) window.clearTimeout(t);
            t = window.setTimeout(() => {
                form.submit();
            }, 450);
        };

        input.addEventListener('input', () => {
            debounceSubmit();
        });

        select.addEventListener('change', () => {
            form.submit();
        });
    })();
</script>
@endsection
