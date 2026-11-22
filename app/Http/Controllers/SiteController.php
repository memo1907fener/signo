<?php

namespace App\Http\Controllers;

use App\Models\Page;

class SiteController extends Controller
{
    public function home()
    {
        // Beispielseiten für die Startseite (werden nicht gespeichert)
        $demo = collect(\App\Support\Starter::sets())->only(['gastro', 'beauty', 'retail'])
            ->map(fn ($set) => new Page($set[0] + ['slug' => 'demo']));
        $demo = $demo->values();
        $demo->push(new Page(\App\Support\Starter::sets()['gastro'][1] + ['slug' => 'demo']));

        return view('site.home', ['demo' => $demo]);
    }

    public function legal(string $doc)
    {
        $titles = ['impressum' => 'Impressum', 'datenschutz' => 'Datenschutzerklärung', 'agb' => 'Allgemeine Geschäftsbedingungen'];
        return view('site.legal', ['doc' => $doc, 'title' => $titles[$doc]]);
    }
}
