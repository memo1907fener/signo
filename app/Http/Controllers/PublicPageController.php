<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $page = Page::with('organization')->where('slug', $slug)->firstOrFail();
        $org = $page->organization;

        if (! $page->is_public) abort(404);
        if (! $org->isActive()) {
            return response()->view('site.paused', ['org' => $org], 503);
        }

        // Statistik: Aufruf zählen (keine Suchmaschinen, keine Vorschauen von Messengern)
        if (! preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp|slurp/i', (string) $request->userAgent())) {
            try {
                $day = now()->timezone(config('app.timezone'))->format('Y-m-d');
                $n = \Illuminate\Support\Facades\DB::table('page_views')->where('page_id', $page->id)->where('day', $day)->increment('views');
                if (! $n) \Illuminate\Support\Facades\DB::table('page_views')->insertOrIgnore(['organization_id' => $org->id, 'page_id' => $page->id, 'day' => $day, 'views' => 1]);
            } catch (\Throwable $e) { /* Statistik darf die Seite nie stören */ }
        }

        $others = $org->pages()->where('is_public', true)->whereNotIn('template', ['media', 'orders'])->get(['id', 'title', 'slug']);

        // Sprache: ?lang=en, sonst Browser-Sprache
        $available = $page->availableLanguages();
        $lang = $request->query('lang');
        if ($lang !== 'de' && ! in_array($lang, $available, true)) {
            $lang = \App\Support\Translations::fromBrowser($request->header('Accept-Language'), $available) ?? 'de';
        }

        return response()->view('site.mobile', [
            'page' => $page->inLanguage($lang),
            'org' => $org,
            'others' => $others,
            'lang' => $lang,
            'available' => $available,
            'ordering' => $org->ordering_enabled && $page->orderable && $page->template === 'list',
        ])->header('Cache-Control', 'private, max-age=30')->header('Vary', 'Accept-Language');
    }

    /** Vollbild-Vorschau im Dashboard (nur eigene Seiten). */
    public function preview(Request $request, Page $page)
    {
        abort_unless($page->organization_id === $request->user()->organization_id, 404);
        return view('site.preview', ['page' => $page, 'portrait' => $request->boolean('portrait')]);
    }
}
