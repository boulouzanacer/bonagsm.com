<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="frsTheme()"
      x-init="init()"
      :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Espace Fournisseur' }} - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            darkMode: 'class',
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --frs-primary:#1E6FD9;
            --frs-bg:#1A1A2E;
            --frs-card:#252543;
        }
        html:not(.dark){
            --frs-bg:#F8FAFC;
            --frs-card:#FFFFFF;
        }
        html:not(.dark) .text-white\/80{color:rgb(30 41 59 / 1);}
        html:not(.dark) .text-white\/70{color:rgb(71 85 105 / 1);}
        html:not(.dark) .text-white\/60{color:rgb(100 116 139 / 1);}
        html:not(.dark) .text-white\/50{color:rgb(100 116 139 / 1);}
        html:not(.dark) .border-white\/10{border-color:rgb(226 232 240 / 1);}
        html:not(.dark) .divide-white\/10 > :not([hidden]) ~ :not([hidden]){border-color:rgb(226 232 240 / 1);}
        html:not(.dark) .bg-black\/20{background-color:rgb(248 250 252 / 1);}
        html:not(.dark) .bg-black\/30{background-color:rgb(241 245 249 / 1);}
        html:not(.dark) .bg-white\/10{background-color:rgb(241 245 249 / 1);}
        html:not(.dark) .hover\:bg-white\/10:hover{background-color:rgb(241 245 249 / 1);}
        html:not(.dark) .text-red-200{color:rgb(185 28 28 / 1);}
        html:not(.dark) .text-emerald-200{color:rgb(4 120 87 / 1);}
        html:not(.dark) .text-amber-200{color:rgb(180 83 9 / 1);}
        html:not(.dark) .text-sky-200{color:rgb(3 105 161 / 1);}
        html:not(.dark) .text-violet-200{color:rgb(109 40 217 / 1);}
        html,body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
    </style>

    <script>
        function frsTheme() {
            return {
                dark: true,
                profileOpen: false,
                init() {
                    const stored = localStorage.getItem('frs_theme');
                    if (stored === 'light') this.dark = false;
                    if (stored === 'dark') this.dark = true;
                    if (!stored) this.dark = true;
                },
                toggleTheme() {
                    this.dark = !this.dark;
                    localStorage.setItem('frs_theme', this.dark ? 'dark' : 'light');
                }
            }
        }
    </script>
</head>
<body class="min-h-screen text-slate-100"
      :class="dark ? 'bg-[var(--frs-bg)]' : 'bg-slate-100 text-slate-900'">
@php($frs = \App\Models\Fournisseur::find(session('frs_id')))
@php($frsUser = (string) session('role', '') === 'frs_user' ? \App\Models\FrsUser::find((int) session('frs_user_id')) : null)
@php($isAdmin = (int) session('is_admin', 0) === 1 || (string) session('role', '') === 'fournisseur')
<div class="flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 w-[240px] border-r bg-[var(--frs-bg)]"
           :class="dark ? 'border-white/10' : 'border-slate-200'">
        <div class="h-16 px-5 flex items-center gap-3 border-b"
             :class="dark ? 'border-white/10' : 'border-slate-200'">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center font-extrabold text-white"
                 style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                S
            </div>
            <div class="leading-tight">
                <div class="font-extrabold tracking-wide">Soltane Dz</div>
                <div class="text-xs" :class="dark ? 'text-white/60' : 'text-slate-500'">Espace Fournisseur</div>
            </div>
        </div>

        <nav class="px-3 py-4 space-y-1 text-sm" :class="dark ? 'text-slate-100' : 'text-slate-900'">
            <a href="{{ url('/fournisseur/dashboard') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/dashboard') ? 'bg-white/10' : '' }}"
               :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                <i class="fa-solid fa-chart-line w-5 text-[var(--frs-primary)]"></i>
                <span>Mon Dashboard</span>
            </a>

            <a href="{{ url('/fournisseur/produits') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/produits*') ? 'bg-white/10' : '' }}"
               :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                <i class="fa-solid fa-boxes-stacked w-5 text-[var(--frs-primary)]"></i>
                <span>Mes Produits</span>
            </a>

            <a href="{{ url('/fournisseur/categories') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/categories*') ? 'bg-white/10' : '' }}"
               :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                <i class="fa-solid fa-layer-group w-5 text-[var(--frs-primary)]"></i>
                <span>Catégories</span>
            </a>

            <a href="{{ url('/fournisseur/clients') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/clients*') ? 'bg-white/10' : '' }}"
               :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                <i class="fa-solid fa-users w-5 text-[var(--frs-primary)]"></i>
                <span>Mes Clients</span>
            </a>

            <a href="{{ url('/fournisseur/commandes') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/commandes*') ? 'bg-white/10' : '' }}"
               :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                <i class="fa-solid fa-cart-shopping w-5 text-[var(--frs-primary)]"></i>
                <span>Mes Commandes</span>
            </a>

            <a href="{{ url('/fournisseur/frais-livraison') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/frais-livraison') ? 'bg-white/10' : '' }}"
               :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                <i class="fa-solid fa-truck-fast w-5 text-[var(--frs-primary)]"></i>
                <span>Frais de livraison</span>
            </a>

            @if($isAdmin)
                <a href="{{ url('/fournisseur/profil') }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/profil') ? 'bg-white/10' : '' }}"
                   :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                    <i class="fa-solid fa-user w-5 text-[var(--frs-primary)]"></i>
                    <span>Paramètres Fournisseur</span>
                </a>

                <a href="{{ url('/fournisseur/parametres-site') }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/parametres-site') ? 'bg-white/10' : '' }}"
                   :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                    <i class="fa-solid fa-sliders w-5 text-[var(--frs-primary)]"></i>
                    <span>Paramètres Site Web</span>
                </a>

                <a href="{{ url('/fournisseur/utilisateurs') }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->is('fournisseur/utilisateurs*') ? 'bg-white/10' : '' }}"
                   :class="dark ? 'hover:bg-white/10' : 'hover:bg-slate-100'">
                    <i class="fa-solid fa-user-gear w-5 text-[var(--frs-primary)]"></i>
                    <span>Gestion Utilisateurs</span>
                </a>
            @endif

            <form method="POST" action="{{ url('/fournisseur/logout') }}" class="pt-2">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 rounded-xl px-4 py-3 hover:bg-white/10 text-left">
                    <i class="fa-solid fa-right-from-bracket w-5 text-red-300"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </nav>
    </aside>

    <div class="flex-1 ml-[240px]">
        <header class="sticky top-0 z-40 h-16 flex items-center justify-between px-6 border-b border-white/10 backdrop-blur"
                :class="dark ? 'bg-[color:rgba(26,26,46,0.85)]' : 'bg-white/80 border-slate-200'">
            <div class="font-extrabold tracking-wide text-lg">
                {{ $title ?? 'Espace Fournisseur' }}
            </div>

            <div class="flex items-center gap-4">
                <button type="button"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border border-white/10 hover:bg-white/10"
                        :class="dark ? 'text-white' : 'border-slate-200 hover:bg-slate-100'"
                        @click="toggleTheme()">
                    <i class="fa-solid" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
                    <span x-text="dark ? 'Clair' : 'Sombre'"></span>
                </button>

                <div class="relative" @click.outside="profileOpen = false">
                    <button type="button"
                            class="flex items-center gap-3 rounded-xl px-3 py-2 border border-white/10 hover:bg-white/10"
                            :class="dark ? '' : 'border-slate-200 hover:bg-slate-100'"
                            @click="profileOpen = !profileOpen">
                        @if(($frs?->logo_url ?? '') !== '')
                            <img src="{{ $frs->logo_url }}"
                                 alt=""
                                 class="h-9 w-9 rounded-xl object-contain border border-white/10 bg-white p-1">
                        @else
                            <div class="h-9 w-9 rounded-full flex items-center justify-center font-bold"
                                 style="background: linear-gradient(135deg, var(--frs-primary), #0A3D7A);">
                                {{ strtoupper(substr($frsUser?->nom ?? $frs?->nom_frs ?? 'F', 0, 1)) }}
                            </div>
                        @endif
                        <div class="text-left leading-tight hidden sm:block max-w-[180px]">
                            <div class="text-sm font-bold truncate">
                                {{ $frsUser ? trim(($frsUser->prenom ?? '').' '.($frsUser->nom ?? '')) : ($frs?->nom_frs ?? 'Fournisseur') }}
                            </div>
                            <div class="text-xs opacity-70 truncate">{{ $frsUser?->email ?? $frs?->email }}</div>
                            <div class="mt-1">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-bold {{ (int)($frs?->actif ?? 0) === 1 ? 'border-emerald-400/20 bg-emerald-500/15 text-emerald-300' : 'border-red-400/20 bg-red-500/15 text-red-300' }}">
                                    {{ (int)($frs?->actif ?? 0) === 1 ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs opacity-70"></i>
                    </button>

                    <div x-show="profileOpen"
                         x-transition
                         class="absolute right-0 mt-2 w-52 rounded-xl border border-white/10 shadow-2xl overflow-hidden"
                         :class="dark ? 'bg-[var(--frs-card)]' : 'bg-white border-slate-200'">
                        <div class="px-4 py-3 text-xs font-bold border-b border-white/10"
                             :class="dark ? '' : 'border-slate-200'">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 {{ (int)($frs?->actif ?? 0) === 1 ? 'border-emerald-400/20 bg-emerald-500/15 text-emerald-300' : 'border-red-400/20 bg-red-500/15 text-red-300' }}">
                                {{ (int)($frs?->actif ?? 0) === 1 ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        @if($isAdmin)
                            <a href="{{ url('/fournisseur/profil') }}"
                               class="block px-4 py-3 text-sm hover:bg-white/10"
                               :class="dark ? '' : 'hover:bg-slate-100'">
                                Paramètres fournisseur
                            </a>
                        @endif
                        @if($isAdmin)
                            <a href="{{ url('/fournisseur/parametres-site') }}"
                               class="block px-4 py-3 text-sm hover:bg-white/10"
                               :class="dark ? '' : 'hover:bg-slate-100'">
                                Paramètres site
                            </a>
                        @endif
                        <form method="POST" action="{{ url('/fournisseur/logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-4 py-3 text-sm hover:bg-white/10"
                                    :class="dark ? '' : 'hover:bg-slate-100'">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-6">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
