<?php

namespace App\Http\Controllers;

use App\Models\Page;

class SiteController extends Controller
{
    public function home()
    {
        // Beispielseiten je Branche (werden nicht gespeichert)
        $make = fn (array $p) => new Page($p + ['slug' => 'demo']);
        $industries = [];
        foreach (\App\Support\Starter::sets() as $key => $set) {
            $industries[$key] = [
                'label' => config("signo.industries.$key.label"),
                'icon' => config("signo.industries.$key.icon"),
                'pages' => array_map($make, $set),
            ];
        }

        return view('site.home', [
            'industries' => $industries,
            'heroPages' => array_map($make, [
                \App\Support\Starter::sets()['gastro'][0],
                \App\Support\Starter::sets()['gastro'][1],
                \App\Support\Starter::sets()['beauty'][0],
                \App\Support\Starter::sets()['retail'][0],
            ]),
            'demoText' => "Mittagsmenü\nSuppen\nFrittatensuppe 4,90\nKürbiscremesuppe – mit Kernöl 5,20\nHauptspeisen\nWiener Schnitzel – mit Erdäpfelsalat 14,90\nKäsespätzle – mit Röstzwiebeln 11,50\nGemüsecurry – vegan, mit Basmatireis 10,90\nDessert\nApfelstrudel – warm, mit Vanillesauce 5,50",
            'aiDemo' => \App\Services\AiImport::available(),
        ]);
    }

    public function legal(string $doc)
    {
        $titles = ['impressum' => 'Impressum', 'datenschutz' => 'Datenschutzerklärung', 'agb' => 'Allgemeine Geschäftsbedingungen'];
        return view('site.legal', ['doc' => $doc, 'title' => $titles[$doc]]);
    }
}
