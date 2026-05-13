<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Boutique' }} - {{ config('app.name') }}</title>

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --store-primary:#1E6FD9;
            --store-bg:#F8FAFC;
            --store-card:#FFFFFF;
        }
        html,body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
    </style>
</head>
<body class="min-h-screen bg-[var(--store-bg)] text-slate-900 overflow-x-hidden">
@php($cartCount = is_array(session('cart')) ? count(session('cart')) : 0)
@php($storeFrs = $boutique ?? \App\Models\Fournisseur::single())
@php($metaPixelId = trim((string)($storeFrs?->meta_pixel_id ?? '')))
@php($tiktokPixelId = trim((string)($storeFrs?->tiktok_pixel_id ?? '')))
<div class="min-h-screen flex flex-col">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0">
                @if(($storeFrs?->logo_url ?? '') !== '')
                    <img src="{{ $storeFrs->logo_url }}"
                         alt=""
                         class="h-10 w-10 rounded-xl object-cover border border-slate-200 bg-white">
                @else
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center font-extrabold text-white"
                         style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);">
                        {{ strtoupper(substr((string)($storeFrs?->nom_frs ?? 'S'), 0, 1)) }}
                    </div>
                @endif
                <div class="leading-tight min-w-0">
                    <div class="font-extrabold tracking-wide truncate">{{ $storeFrs?->nom_frs ?? config('app.name') }}</div>
                    <div class="text-xs text-slate-500">
                        @if(($storeFrs?->telephone ?? '') !== '')
                            <a href="tel:{{ $storeFrs->telephone }}" class="hover:underline">{{ $storeFrs->telephone }}</a>
                        @else
                            Store
                        @endif
                        @if(($storeFrs?->google_maps_url ?? '') !== '')
                            <span class="mx-2 text-slate-300">•</span>
                            <a href="{{ $storeFrs->google_maps_url }}" target="_blank" class="hover:underline">Localisation</a>
                        @endif
                    </div>
                </div>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ url('/panier') }}"
                   class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-[var(--store-primary)]"></i>
                    <span>Panier</span>
                    <span class="ml-1 inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 rounded-full text-xs font-extrabold bg-slate-100 text-slate-700">
                        {{ $cartCount }}
                    </span>
                </a>

                @if(($client ?? null))
                    <a href="{{ url('/mes-commandes') }}"
                       class="hidden sm:inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50">
                        <i class="fa-solid fa-receipt text-[var(--store-primary)]"></i>
                        <span>Mes commandes</span>
                    </a>
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50">
                            <i class="fa-solid fa-right-from-bracket text-red-600"></i>
                            <span class="hidden sm:inline">Déconnexion</span>
                        </button>
                    </form>
                @else
                    <a href="{{ url('/login') }}"
                       class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border border-slate-200 bg-white hover:bg-slate-50">
                        <i class="fa-solid fa-user text-[var(--store-primary)]"></i>
                        <span>Connexion</span>
                    </a>
                    <a href="{{ url('/register') }}"
                       class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-extrabold text-white"
                       style="background: linear-gradient(135deg, var(--store-primary), #0A3D7A);">
                        <i class="fa-solid fa-user-plus"></i>
                        <span class="hidden sm:inline">Créer compte</span>
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 py-6">
            @if(session('success'))
                <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('info'))
                <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sky-800">
                    {{ session('info') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 py-6 text-sm text-slate-500">
            © {{ date('Y') }} {{ config('app.name') }}
        </div>
    </footer>
</div>

@if($metaPixelId !== '')
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($metaPixelId));
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ urlencode($metaPixelId) }}&ev=PageView&noscript=1"/></noscript>
@endif

@if($tiktokPixelId !== '')
    <script>
        !function (w, d, t) { w.TiktokAnalyticsObject=t; var ttq=w[t]=w[t]||[]; ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"]; ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}; for(var i=0;i<ttq.methods.length;i++) ttq.setAndDefer(ttq,ttq.methods[i]); ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++) ttq.setAndDefer(e,ttq.methods[n]); return e}; ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js"; ttq._i=ttq._i||{}; ttq._i[e]=[]; ttq._i[e]._u=i; ttq._t=ttq._t||{}; ttq._t[e]=+new Date; ttq._o=ttq._o||{}; ttq._o[e]=n||{}; var o=d.createElement("script"); o.type="text/javascript"; o.async=!0; o.src=i+"?sdkid="+e+"&lib="+t; var a=d.getElementsByTagName("script")[0]; a.parentNode.insertBefore(o,a)}; ttq.load(@json($tiktokPixelId)); ttq.page(); }(window, document, 'ttq');
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
