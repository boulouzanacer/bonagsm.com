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
</div>
@endsection
