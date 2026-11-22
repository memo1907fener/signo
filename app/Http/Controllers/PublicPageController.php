<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::with('organization')->where('slug', $slug)->firstOrFail();
        $org = $page->organization;

        if (! $page->is_public) abort(404);
        if (! $org->isActive()) {
            return response()->view('site.paused', ['org' => $org], 503);
        }

        $others = $org->pages()->where('is_public', true)->where('template', '!=', 'media')->get(['id', 'title', 'slug']);

        return response()->view('site.mobile', ['page' => $page, 'org' => $org, 'others' => $others])
            ->header('Cache-Control', 'public, max-age=60');
    }

    /** Vollbild-Vorschau im Dashboard (nur eigene Seiten). */
    public function preview(Request $request, Page $page)
    {
        abort_unless($page->organization_id === $request->user()->organization_id, 404);
        return view('site.preview', ['page' => $page, 'portrait' => $request->boolean('portrait')]);
    }
}
