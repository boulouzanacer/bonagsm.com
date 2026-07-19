<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($isRtl ?? false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __($title ?? 'Boutique') }} - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            darkMode: 'class',
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --store-primary:#0f7a43;
            --store-primary-dark:#0b5e33;
            --store-accent:#111827;
            --store-bg:#f3f7f5;
            --store-card:#ffffff;
            --store-border:rgba(15, 23, 42, 0.08);
            --store-shadow:0 20px 60px rgba(15, 23, 42, 0.08);
        }
        html,body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
        html[dir="rtl"], html[dir="rtl"] body{font-family:Tajawal,Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
        body{
            background:
                radial-gradient(circle at top left, rgba(15,122,67,0.12), transparent 30%),
                radial-gradient(circle at top right, rgba(17,24,39,0.08), transparent 28%),
                linear-gradient(180deg, #f8fbfa 0%, var(--store-bg) 45%, #eef4f1 100%);
        }
        .store-shell{
            position:relative;
        }
        .store-shell::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            background-image:
                linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size:32px 32px;
            mask-image:linear-gradient(180deg, rgba(0,0,0,0.25), transparent 80%);
            opacity:.5;
        }
        .glass-card{
            background:rgba(255,255,255,0.82);
            backdrop-filter:blur(18px);
            border:1px solid rgba(255,255,255,0.7);
            box-shadow:var(--store-shadow);
        }
        .soft-card{
            background:linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.92));
            border:1px solid var(--store-border);
            box-shadow:var(--store-shadow);
        }
        .hero-orb{
            position:absolute;
            border-radius:9999px;
            filter:blur(60px);
            opacity:.4;
            pointer-events:none;
        }
        .hero-orb-a{background:rgba(15,122,67,0.25);}
        .hero-orb-b{background:rgba(59,130,246,0.18);}
        .animate-float{
            animation:floatY 6s ease-in-out infinite;
        }
        .animate-float-delay{
            animation:floatY 7.5s ease-in-out infinite;
            animation-delay:-1.5s;
        }
        .interactive-lift{
            transition:transform .24s ease, box-shadow .24s ease, border-color .24s ease, background-color .24s ease;
        }
        .interactive-lift:hover{
            transform:translateY(-3px);
            box-shadow:0 18px 45px rgba(15, 23, 42, 0.12);
        }
        .store-gradient{
            background:linear-gradient(135deg, var(--store-primary) 0%, var(--store-primary-dark) 55%, #111827 100%);
        }
        @keyframes floatY{
            0%,100%{transform:translateY(0);}
            50%{transform:translateY(-10px);}
        }
        html[dir="rtl"] .store-search-icon{
            left:auto;
            right:1rem;
        }
        html[dir="rtl"] .store-search-input{
            padding-left:1rem;
            padding-right:2.75rem;
        }
        html[dir="rtl"] .header-counter{
            margin-left:0;
            margin-right:.25rem;
        }
        html[dir="rtl"] .gallery-prev{
            left:auto;
            right:.75rem;
        }
        html[dir="rtl"] .gallery-next{
            right:auto;
            left:.75rem;
        }
        html[dir="rtl"] .gallery-counter{
            right:auto;
            left:.75rem;
        }
        html[dir="rtl"] .table-align-start{
            text-align:right;
        }
        html[dir="rtl"] .table-align-end{
            text-align:left;
        }
        html[dir="rtl"] .force-ltr{
            direction:ltr;
            unicode-bidi:isolate;
        }
        html[dir="rtl"] input.force-ltr{
            text-align:left;
        }
    </style>
</head>
<body class="min-h-screen bg-[var(--store-bg)] text-slate-900 overflow-x-hidden">
@php($cartCount = is_array(session('cart')) ? count(session('cart')) : 0)
@php($storeFrs = $boutique ?? \App\Models\Fournisseur::single())
@php($metaPixelId = trim((string)($storeFrs?->meta_pixel_id ?? '')))
@php($tiktokPixelId = trim((string)($storeFrs?->tiktok_pixel_id ?? '')))
@php($wishlistCount = (int)($wishlist_count ?? 0))
<div class="store-shell min-h-screen flex flex-col">
    <div class="hero-orb hero-orb-a h-64 w-64 -top-10 -left-10 animate-float"></div>
    <div class="hero-orb hero-orb-b h-72 w-72 top-28 right-0 animate-float-delay"></div>
    <header class="sticky top-0 z-40 border-b border-white/50 bg-white/70 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="grid grid-cols-[auto,1fr] items-center gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-between">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 min-w-0">
                    @if(($storeFrs?->logo_url ?? '') !== '')
                        <img src="{{ $storeFrs->logo_url }}"
                             alt=""
                             class="h-12 w-12 sm:h-14 sm:w-14 rounded-2xl object-contain border border-white/80 bg-white p-1.5 flex-shrink-0 shadow-lg shadow-slate-200/80">
                    @else
                        <div class="h-12 w-12 sm:h-14 sm:w-14 rounded-2xl flex items-center justify-center font-extrabold text-white flex-shrink-0 store-gradient shadow-lg shadow-emerald-900/20">
                            {{ strtoupper(substr((string)($storeFrs?->nom_frs ?? 'S'), 0, 1)) }}
                        </div>
                    @endif

                    <div class="leading-tight min-w-0 hidden sm:block">
                        <div class="flex items-center gap-2">
                            <div class="font-extrabold tracking-wide truncate">{{ $storeFrs?->nom_frs ?? config('app.name') }}</div>
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.2em] text-emerald-700">{{ __('Store') }}</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            @if(($storeFrs?->telephone ?? '') !== '')
                                <a href="tel:{{ $storeFrs->telephone }}" class="hover:underline force-ltr">{{ $storeFrs->telephone }}</a>
                            @else
                                {{ __('Store') }}
                            @endif
                            @if(($storeFrs?->google_maps_url ?? '') !== '')
                                <span class="mx-2 text-slate-300">•</span>
                                <a href="{{ $storeFrs->google_maps_url }}" target="_blank" class="hover:underline">{{ __('Localisation') }}</a>
                            @endif
                        </div>
                    </div>
                </a>

                <a href="{{ url('/') }}" class="text-right min-w-0 flex flex-col justify-center sm:hidden">
                    <div class="font-extrabold tracking-wide truncate">{{ $storeFrs?->nom_frs ?? config('app.name') }}</div>
                    <div class="text-xs text-slate-500 truncate">
                        @if(($storeFrs?->telephone ?? '') !== '')
                            <span class="force-ltr">{{ $storeFrs->telephone }}</span>
                        @else
                            <span>{{ __('Store') }}</span>
                        @endif
                    </div>
                    @if(($storeFrs?->google_maps_url ?? '') !== '')
                        <div class="text-xs text-slate-500 truncate">
                            <span>{{ __('Localisation') }}</span>
                        </div>
                    @endif
                </a>

                <div class="col-span-2 flex items-center gap-2 justify-end sm:col-span-1 sm:justify-end">
                    <div class="inline-flex items-center rounded-2xl border border-white/70 bg-white/90 p-1 shadow-sm">
                        <a href="{{ url('/locale/fr') }}"
                           class="rounded-xl px-2.5 py-1.5 text-xs font-extrabold transition {{ app()->getLocale() === 'fr' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:text-slate-900' }}">
                            FR
                        </a>
                        <a href="{{ url('/locale/ar') }}"
                           class="rounded-xl px-2.5 py-1.5 text-xs font-extrabold transition {{ app()->getLocale() === 'ar' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:text-slate-900' }}">
                            AR
                        </a>
                    </div>

                    <a href="{{ url('/wishlist') }}"
                       class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold border border-white/70 bg-white/90 hover:bg-white shadow-sm">
                        <i class="fa-regular fa-heart text-[var(--store-primary)]"></i>
                        <span class="hidden sm:inline">{{ __('Favoris') }}</span>
                        <span class="header-counter ml-1 inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700">
                            {{ $wishlistCount }}
                        </span>
                    </a>

                    <a href="{{ url('/panier') }}"
                       class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold border border-white/70 bg-white/90 hover:bg-white shadow-sm">
                        <i class="fa-solid fa-cart-shopping text-[var(--store-primary)]"></i>
                        <span class="hidden sm:inline">{{ __('Panier') }}</span>
                        <span class="header-counter ml-1 inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700">
                            {{ $cartCount }}
                        </span>
                    </a>

                    @if(($client ?? null))
                        <a href="{{ url('/mes-commandes') }}"
                           class="interactive-lift hidden sm:inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold border border-white/70 bg-white/90 hover:bg-white shadow-sm">
                            <i class="fa-solid fa-receipt text-[var(--store-primary)]"></i>
                            <span>{{ __('Mes commandes') }}</span>
                        </a>
                        <form method="POST" action="{{ url('/logout') }}">
                            @csrf
                            <button type="submit"
                                    class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold border border-white/70 bg-white/90 hover:bg-white shadow-sm">
                                <i class="fa-solid fa-right-from-bracket text-red-600"></i>
                                <span class="hidden sm:inline">{{ __('Déconnexion') }}</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ url('/login') }}"
                           class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold border border-white/70 bg-white/90 hover:bg-white shadow-sm">
                            <i class="fa-solid fa-user text-[var(--store-primary)]"></i>
                            <span class="hidden sm:inline">{{ __('Connexion') }}</span>
                        </a>
                        <a href="{{ url('/register') }}"
                           class="interactive-lift inline-flex items-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-extrabold text-white store-gradient shadow-lg shadow-emerald-950/20">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="hidden sm:inline">{{ __('Créer compte') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:py-8">
            @if(session('success'))
                <div class="mb-4 rounded-2xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('info'))
                <div class="mb-4 rounded-2xl border border-sky-200/80 bg-sky-50/90 px-4 py-3 text-sky-800 shadow-sm">
                    {{ session('info') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-2xl border border-red-200/80 bg-red-50/90 px-4 py-3 text-red-800 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="border-t border-white/60 bg-white/70 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 py-6 text-sm text-slate-500">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>© {{ date('Y') }} {{ config('app.name') }}</div>
                <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.2em] text-slate-400">
                    <span>{{ __('Responsive') }}</span>
                    <span>{{ __('Secure Checkout') }}</span>
                    <span>{{ __('Modern UI') }}</span>
                </div>
            </div>
        </div>
    </footer>
</div>

@if($metaPixelId !== '')
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ addslashes($metaPixelId) }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ urlencode($metaPixelId) }}&ev=PageView&noscript=1"/></noscript>
@endif

@if($tiktokPixelId !== '')
    <script>
        !function (w, d, t) { w.TiktokAnalyticsObject=t; var ttq=w[t]=w[t]||[]; ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"]; ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}; for(var i=0;i<ttq.methods.length;i++) ttq.setAndDefer(ttq,ttq.methods[i]); ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++) ttq.setAndDefer(e,ttq.methods[n]); return e}; ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js"; ttq._i=ttq._i||{}; ttq._i[e]=[]; ttq._i[e]._u=i; ttq._t=ttq._t||{}; ttq._t[e]=+new Date; ttq._o=ttq._o||{}; ttq._o[e]=n||{}; var o=d.createElement("script"); o.type="text/javascript"; o.async=!0; o.src=i+"?sdkid="+e+"&lib="+t; var a=d.getElementsByTagName("script")[0]; a.parentNode.insertBefore(o,a)}; ttq.load('{{ addslashes($tiktokPixelId) }}'); ttq.page(); }(window, document, 'ttq');
    </script>
@endif

<script>
    (function () {
        const currency = 'DZD';
        const hasMeta = typeof window.fbq === 'function';
        const hasTikTok = typeof window.ttq === 'function';

        function normalizeContents(raw) {
            const rows = Array.isArray(raw) ? raw : [];
            return rows.map((r) => {
                const id = (r && (r.content_id ?? r.id ?? r.product_id)) ? String(r.content_id ?? r.id ?? r.product_id) : '';
                const quantity = Number(r && (r.quantity ?? r.qty)) || 1;
                const price = r && (r.price ?? r.unit_price);
                const out = { id, quantity };
                if (price !== undefined && price !== null && Number.isFinite(Number(price))) {
                    out.price = Number(price);
                }
                return out;
            }).filter((r) => r.id !== '');
        }

        window.trackAddToCart = function (payload) {
            const p = payload || {};
            const value = Number(p.value || 0);
            const qty = Number(p.quantity || 1);
            const id = p.product_id ? String(p.product_id) : '';
            const unitPrice = Number.isFinite(Number(p.unit_price)) ? Number(p.unit_price) : (qty > 0 ? value / qty : 0);
            if (hasMeta && id) {
                const metaContents = Number.isFinite(unitPrice) && unitPrice > 0
                    ? [{ id: id, quantity: qty, item_price: unitPrice }]
                    : [{ id: id, quantity: qty }];
                window.fbq('track', 'AddToCart', { content_ids: [id], content_type: 'product', value: value, currency: currency, contents: metaContents });
            }
            if (hasTikTok && id) {
                const contents = Number.isFinite(unitPrice) && unitPrice > 0
                    ? [{ content_id: id, content_type: 'product', quantity: qty, price: unitPrice }]
                    : [{ content_id: id, content_type: 'product', quantity: qty }];
                window.ttq.track('AddToCart', { value: value, currency: currency, contents: contents });
            }
        };

        window.trackInitiateCheckout = function (payload) {
            const p = payload || {};
            const value = Number(p.value || 0);
            const normalized = normalizeContents(p.contents);
            const metaContents = normalized.map((r) => {
                if (r.price !== undefined) return { id: r.id, quantity: r.quantity, item_price: r.price };
                return { id: r.id, quantity: r.quantity };
            });
            const tiktokContents = normalized.map((r) => {
                if (r.price !== undefined) return { content_id: r.id, content_type: 'product', quantity: r.quantity, price: r.price };
                return { content_id: r.id, content_type: 'product', quantity: r.quantity };
            });
            if (hasMeta) {
                window.fbq('track', 'InitiateCheckout', { value: value, currency: currency, contents: metaContents });
            }
            if (hasTikTok) {
                window.ttq.track('InitiateCheckout', { value: value, currency: currency, contents: tiktokContents });
            }
        };

        window.trackPurchase = function (payload) {
            const p = payload || {};
            const value = Number(p.value || 0);
            const orderId = p.order_id ? String(p.order_id) : '';
            const normalized = normalizeContents(p.contents);
            const metaContents = normalized.map((r) => {
                if (r.price !== undefined) return { id: r.id, quantity: r.quantity, item_price: r.price };
                return { id: r.id, quantity: r.quantity };
            });
            const tiktokContents = normalized.map((r) => {
                if (r.price !== undefined) return { content_id: r.id, content_type: 'product', quantity: r.quantity, price: r.price };
                return { content_id: r.id, content_type: 'product', quantity: r.quantity };
            });
            if (hasMeta) {
                window.fbq('track', 'Purchase', { value: value, currency: currency, contents: metaContents, order_id: orderId });
            }
            if (hasTikTok) {
                window.ttq.track('CompletePayment', { value: value, currency: currency, order_id: orderId, contents: tiktokContents });
            }
        };

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            const action = (form.getAttribute('action') || '');
            if (!action.includes('/panier/add')) return;
            const pid = form.getAttribute('data-pixel-product-id') || '';
            if (!pid) return;
            const qtyEl = form.querySelector('input[name="qty"]');
            const qty = qtyEl ? Number(qtyEl.value || 1) : 1;
            const price = Number(form.getAttribute('data-pixel-price') || 0);
            window.trackAddToCart({ product_id: pid, quantity: qty, unit_price: price, value: price * qty });
        }, true);
    })();
</script>
</body>
</html>
