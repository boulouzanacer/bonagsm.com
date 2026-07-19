<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @var array<int, string>
     */
    private array $supportedLocales = ['fr', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->session()->get('locale', '');

        if ($locale === '') {
            $locale = (string) $request->cookie('locale', '');
        }

        if (! in_array($locale, $this->supportedLocales, true)) {
            $preferred = $request->getPreferredLanguage($this->supportedLocales);
            $locale = in_array($preferred, $this->supportedLocales, true) ? $preferred : 'fr';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        View::share('currentLocale', $locale);
        View::share('isRtl', $locale === 'ar');
        View::share('supportedLocales', $this->supportedLocales);

        return $next($request);
    }
}
