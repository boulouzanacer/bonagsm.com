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
        <div class="text-2xl font-extrabold tracking-wide">Informations</div>

        <form method="POST" action="{{ url('/fournisseur/profil') }}" class="mt-5" id="profilForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Nom</label>
                    <input name="nom_frs"
                           value="{{ old('nom_frs', $frs->nom_frs) }}"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Téléphone</label>
                    <input name="telephone"
                           value="{{ old('telephone', $frs->telephone) }}"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-white/70 mb-1">Logo (optionnel)</label>
                    <input type="file"
                           name="logo"
                           accept="image/png,image/jpeg,image/webp"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                    @if(($frs->logo_path ?? null))
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($frs->logo_path) }}"
                                 alt=""
                                 class="h-14 w-14 rounded-2xl object-cover border border-white/10">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox"
                                       name="remove_logo"
                                       value="1"
                                       class="h-5 w-5 rounded border-white/20 bg-black/20">
                                <span class="text-sm font-semibold text-white/70">Supprimer</span>
                            </label>
                        </div>
                    @endif
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-white/70 mb-1">Adresse</label>
                    <textarea name="adresse"
                              rows="3"
                              class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                              required>{{ old('adresse', $frs->adresse) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Latitude (optionnel)</label>
                    <input name="latitude"
                           id="latInput"
                           value="{{ old('latitude', $frs->latitude ?? '') }}"
                           inputmode="decimal"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Longitude (optionnel)</label>
                    <input name="longitude"
                           id="lngInput"
                           value="{{ old('longitude', $frs->longitude ?? '') }}"
                           inputmode="decimal"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]">
                </div>

                <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-2">
                    <button type="button"
                            id="geoBtn"
                            class="rounded-2xl px-4 py-2 text-sm font-extrabold border border-white/10 hover:bg-white/10">
                        Utiliser ma position
                    </button>
                    @if(($frs->latitude ?? null) && ($frs->longitude ?? null))
                        <a href="https://www.google.com/maps?q={{ $frs->latitude }},{{ $frs->longitude }}"
                           target="_blank"
                           class="text-sm font-bold text-white/70 hover:text-white underline">
                            Ouvrir sur Maps
                        </a>
                    @endif
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox"
                               name="show_prices_to_guests"
                               value="1"
                               class="h-5 w-5 rounded border-white/20 bg-black/20"
                               @checked((int)old('show_prices_to_guests', $frs->show_prices_to_guests ?? 1) === 1)>
                        <span class="text-sm font-semibold text-white/70">Afficher les prix aux invités (non connectés)</span>
                    </label>
                </div>

                <div class="md:col-span-2 pt-2">
                    <div class="text-sm font-extrabold tracking-wide text-white/80">Pixels</div>
                    <div class="text-xs text-white/60 mt-1">Meta Pixel et TikTok Pixel pour le suivi.</div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Meta Pixel ID</label>
                    <input name="meta_pixel_id"
                           value="{{ old('meta_pixel_id', $frs->meta_pixel_id ?? '') }}"
                           inputmode="numeric"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           placeholder="ex: 123456789012345">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">TikTok Pixel ID</label>
                    <input name="tiktok_pixel_id"
                           value="{{ old('tiktok_pixel_id', $frs->tiktok_pixel_id ?? '') }}"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           placeholder="ex: C3ABCDEFG12345">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Wilaya</label>
                    <select name="id_wilaya"
                            id="wilayaSelectFrs"
                            class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                            required>
                        <option value="">Choisir...</option>
                        @foreach($wilayas as $w)
                            <option value="{{ $w->ID_WILAYA }}"
                                    @selected((int)old('id_wilaya', $frs->id_wilaya) === (int)$w->ID_WILAYA)>
                                {{ $w->ID_WILAYA }} - {{ $w->WILAYA }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Commune</label>
                    <select name="id_commune"
                            id="communeSelectFrs"
                            class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                            required>
                        <option value="">Choisir...</option>
                        @foreach($communes as $c)
                            <option value="{{ $c->ID_COMMUNE }}"
                                    @selected((int)old('id_commune', $frs->id_commune) === (int)$c->ID_COMMUNE)>
                                {{ $c->COMMUNE }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="rounded-2xl px-6 py-3 font-extrabold text-white"
                        style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-white/10 bg-[var(--frs-card)] p-6">
        <div class="text-2xl font-extrabold tracking-wide">Mot de passe</div>

        <form method="POST" action="{{ url('/fournisseur/profil/password') }}" class="mt-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Ancien</label>
                    <input type="password"
                           name="old_password"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Nouveau</label>
                    <input type="password"
                           name="password"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white/70 mb-1">Confirmation</label>
                    <input type="password"
                           name="password_confirmation"
                           class="w-full rounded-2xl border border-white/10 bg-black/20 px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
                           required>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="rounded-2xl px-6 py-3 font-extrabold border border-white/10 hover:bg-white/10">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const wilayaSelect = document.getElementById('wilayaSelectFrs');
        const communeSelect = document.getElementById('communeSelectFrs');
        if (!wilayaSelect || !communeSelect) return;

        async function loadCommunes(wilayaId) {
            communeSelect.innerHTML = '<option value="">Chargement...</option>';
            if (!wilayaId) {
                communeSelect.innerHTML = '<option value="">Choisir...</option>';
                return;
            }

            const res = await fetch('{{ url('/fournisseur/wilayas') }}/' + wilayaId + '/communes');
            const rows = await res.json();
            const current = '{{ (int)old('id_commune', $frs->id_commune) }}';

            communeSelect.innerHTML = '<option value="">Choisir...</option>';
            rows.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.ID_COMMUNE;
                opt.textContent = r.COMMUNE;
                if (String(r.ID_COMMUNE) === String(current)) opt.selected = true;
                communeSelect.appendChild(opt);
            });
        }

        wilayaSelect.addEventListener('change', (e) => loadCommunes(e.target.value));
    })();

    (function () {
        const btn = document.getElementById('geoBtn');
        const lat = document.getElementById('latInput');
        const lng = document.getElementById('lngInput');
        if (!btn || !lat || !lng) return;
        btn.addEventListener('click', () => {
            if (!navigator.geolocation) return;
            btn.disabled = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    lat.value = String(pos.coords.latitude);
                    lng.value = String(pos.coords.longitude);
                    btn.disabled = false;
                },
                () => {
                    btn.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    })();
</script>
@endsection
