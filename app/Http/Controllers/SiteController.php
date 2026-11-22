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
            'story' => $this->storyPages($make),
        ]);
    }

    /** Die Speisekarte für die Scroll-Geschichte – auf Deutsch, Englisch und Italienisch. */
    private function storyPages(\Closure $make): array
    {
        $base = \App\Support\Starter::sets()['gastro'][0];
        $texts = [
            'de' => null,
            'en' => [
                'heading' => 'Menu', 'subheading' => 'Fresh & homemade',
                'footer' => 'All prices in euros incl. VAT · Allergen information from our staff',
                'sections' => [
                    ['Starters', [['Frittatensuppe', 'Beef broth with herb pancake strips'], ['Mixed salad', 'With pumpkin seed oil dressing']]],
                    ['Main courses', [['Wiener Schnitzel', 'Veal, with parsley potatoes'], ['Cheese spaetzle', 'With fried onions and green salad'], ['Vegetable curry', 'Vegan, with basmati rice']]],
                    ['Desserts', [['Kaiserschmarrn', 'Shredded pancake with plum compote']]],
                ],
            ],
            'it' => [
                'heading' => 'Menù', 'subheading' => 'Fresco e fatto in casa',
                'footer' => 'Tutti i prezzi in euro, IVA inclusa · Informazioni sugli allergeni dal personale',
                'sections' => [
                    ['Antipasti', [['Frittatensuppe', 'Brodo di manzo con striscioline di crêpe'], ['Insalata mista', 'Con olio di semi di zucca']]],
                    ['Secondi piatti', [['Wiener Schnitzel', 'Di vitello, con patate al prezzemolo'], ['Spätzle al formaggio', 'Con cipolle fritte e insalata verde'], ['Curry di verdure', 'Vegano, con riso basmati']]],
                    ['Dolci', [['Kaiserschmarrn', 'Frittata dolce con composta di prugne']]],
                ],
            ],
        ];
        $out = [];
        foreach ($texts as $lang => $t) {
            $p = $base;
            if ($t) {
                $p['data']['heading'] = $t['heading'];
                $p['data']['subheading'] = $t['subheading'];
                $p['data']['footer'] = $t['footer'];
                foreach ($t['sections'] as $si => [$title, $items]) {
                    $p['data']['sections'][$si]['title'] = $title;
                    foreach ($items as $ii => [$name, $desc]) {
                        $p['data']['sections'][$si]['items'][$ii]['name'] = $name;
                        $p['data']['sections'][$si]['items'][$ii]['description'] = $desc;
                    }
                }
            }
            $out[$lang] = $make($p);
        }
        return $out;
    }

    public function legal(string $doc)
    {
        $titles = ['impressum' => 'Impressum', 'datenschutz' => 'Datenschutzerklärung', 'agb' => 'Allgemeine Geschäftsbedingungen'];
        return view('site.legal', ['doc' => $doc, 'title' => $titles[$doc]]);
    }
}
