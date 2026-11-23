<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Sprache der Oberfläche: Benutzer-Einstellung, sonst Auswahl auf der Website, sonst Browser. */
class SetLocale
{
    public const LOCALES = ['de' => 'Deutsch', 'en' => 'English', 'tr' => 'Türkçe'];

    public function handle(Request $request, Closure $next)
    {
        $pick = strtolower((string) $request->query('sprache'));
        if (isset(self::LOCALES[$pick])) {
            $request->session()->put('locale', $pick);
            if ($request->user()) $request->user()->forceFill(['locale' => $pick])->save();
        }

        $locale = $request->user()?->locale ?: $request->session()->get('locale');
        if (! isset(self::LOCALES[$locale ?? ''])) {
            $locale = 'de';
            foreach (explode(',', (string) $request->header('Accept-Language')) as $part) {
                $code = strtolower(substr(trim($part), 0, 2));
                if (isset(self::LOCALES[$code])) { $locale = $code; break; }
            }
        }
        app()->setLocale($locale);
        \Illuminate\Support\Carbon::setLocale($locale);

        return $next($request);
    }
}
