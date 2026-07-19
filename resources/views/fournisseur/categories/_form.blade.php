@csrf

@if($errors->any())
    <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200 mb-4">
        <ul class="list-disc pl-5 space-y-1 text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('success'))
    <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-emerald-200 mb-4">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-red-200 mb-4">
        {{ session('error') }}
    </div>
@endif

<div>
    <label class="block text-sm font-semibold text-white/70 mb-1">{{ __('Nom') }}</label>
    <input name="nom"
           value="{{ old('nom', $categorie->nom ?? '') }}"
           class="w-full rounded-2xl border border-white/10 bg-[var(--frs-card)] px-4 py-3 outline-none focus:border-[var(--frs-primary)]"
           required>
</div>
