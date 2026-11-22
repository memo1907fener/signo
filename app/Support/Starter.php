<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Page;

/**
 * Beispielinhalte: Nach der Registrierung sieht jeder Kunde sofort fertige Seiten
 * seiner Branche – er muss nur noch Texte und Preise anpassen.
 */
class Starter
{
    /** Leere Grundstruktur je Vorlage. */
    public static function blank(string $template): array
    {
        return match ($template) {
            'list' => [
                'heading' => 'Neue Liste',
                'subheading' => '',
                'sections' => [[
                    'title' => '',
                    'items' => [['name' => '', 'description' => '', 'price' => '']],
                ]],
                'footer' => '',
            ],
            'offer' => [
                'kicker' => 'Angebot',
                'heading' => 'Ihr Angebot',
                'text' => '',
                'price' => '',
                'old_price' => '',
                'image' => null,
                'footer' => '',
            ],
            'info' => [
                'heading' => 'Information',
                'text' => '',
                'rows' => [],
                'image' => null,
                'footer' => '',
            ],
            'media' => [
                'file' => null,
                'mime' => null,
                'fit' => 'cover',
                'caption' => '',
            ],
            default => [],
        };
    }

    public static function create(Organization $org): void
    {
        $sets = self::sets()[$org->industry] ?? self::sets()['other'];
        foreach ($sets as $i => $p) {
            Page::create([
                'organization_id' => $org->id,
                'title' => $p['title'],
                'template' => $p['template'],
                'theme' => $p['theme'] ?? 'light',
                'accent' => $p['accent'] ?? 'coral',
                'font' => $p['font'] ?? 'modern',
                'data' => $p['data'],
                'position' => $i,
            ]);
        }
    }

    private static function hours(): array
    {
        return [
            ['label' => 'Montag – Freitag', 'value' => '08:00 – 18:00'],
            ['label' => 'Samstag', 'value' => '09:00 – 13:00'],
            ['label' => 'Sonntag & Feiertag', 'value' => 'geschlossen'],
        ];
    }

    public static function sets(): array
    {
        return [
            'gastro' => [
                ['title' => 'Speisekarte', 'template' => 'list', 'theme' => 'dark', 'accent' => 'gold', 'font' => 'elegant', 'data' => [
                    'heading' => 'Speisekarte',
                    'subheading' => 'Frisch & hausgemacht',
                    'sections' => [
                        ['title' => 'Vorspeisen', 'items' => [
                            ['name' => 'Frittatensuppe', 'description' => 'Kräftige Rindsuppe mit Kräuterfrittaten', 'price' => '4,90'],
                            ['name' => 'Gemischter Salat', 'description' => 'Mit Kernöl-Dressing', 'price' => '5,50'],
                        ]],
                        ['title' => 'Hauptspeisen', 'items' => [
                            ['name' => 'Wiener Schnitzel', 'description' => 'Vom Kalb, mit Petersilerdäpfeln', 'price' => '18,90'],
                            ['name' => 'Käsespätzle', 'description' => 'Mit Röstzwiebeln und grünem Salat', 'price' => '12,50'],
                            ['name' => 'Gemüsecurry', 'description' => 'Vegan, mit Basmatireis', 'price' => '11,90'],
                        ]],
                        ['title' => 'Nachspeisen', 'items' => [
                            ['name' => 'Kaiserschmarrn', 'description' => 'Mit Zwetschkenröster', 'price' => '8,90'],
                        ]],
                    ],
                    'footer' => 'Alle Preise in Euro inkl. MwSt. · Allergeninformation beim Service',
                ]],
                ['title' => 'Mittagsmenü', 'template' => 'offer', 'theme' => 'light', 'accent' => 'coral', 'data' => [
                    'kicker' => 'Heute zu Mittag',
                    'heading' => 'Tafelspitz mit Apfelkren',
                    'text' => "Inklusive Tagessuppe\nMo – Fr von 11:30 bis 14:00",
                    'price' => '12,90',
                    'old_price' => '',
                    'image' => null,
                    'footer' => '',
                ]],
                ['title' => 'Öffnungszeiten', 'template' => 'info', 'theme' => 'dark', 'accent' => 'gold', 'font' => 'elegant', 'data' => [
                    'heading' => 'Willkommen!',
                    'text' => 'Wir freuen uns auf Ihren Besuch.',
                    'rows' => [
                        ['label' => 'Dienstag – Samstag', 'value' => '11:00 – 22:00'],
                        ['label' => 'Sonntag', 'value' => '11:00 – 15:00'],
                        ['label' => 'Montag', 'value' => 'Ruhetag'],
                    ],
                    'image' => null,
                    'footer' => 'Tischreservierung: 01 234 56 78',
                ]],
            ],
            'beauty' => [
                ['title' => 'Preisliste', 'template' => 'list', 'theme' => 'light', 'accent' => 'rose', 'font' => 'elegant', 'data' => [
                    'heading' => 'Preisliste',
                    'subheading' => 'Damen & Herren',
                    'sections' => [
                        ['title' => 'Damen', 'items' => [
                            ['name' => 'Waschen, Schneiden, Föhnen', 'description' => 'Kurzes bis mittellanges Haar', 'price' => 'ab 45,00'],
                            ['name' => 'Färben', 'description' => 'Ansatz inkl. Pflege', 'price' => 'ab 55,00'],
                            ['name' => 'Strähnen', 'description' => 'Folientechnik', 'price' => 'ab 70,00'],
                        ]],
                        ['title' => 'Herren', 'items' => [
                            ['name' => 'Haarschnitt', 'description' => 'Inkl. Waschen & Styling', 'price' => '28,00'],
                            ['name' => 'Bartpflege', 'description' => 'Konturen & Pflegeöl', 'price' => '15,00'],
                        ]],
                    ],
                    'footer' => 'Termine auch online buchbar',
                ]],
                ['title' => 'Aktion', 'template' => 'offer', 'theme' => 'dark', 'accent' => 'rose', 'data' => [
                    'kicker' => 'Nur diesen Monat',
                    'heading' => 'Pflegebehandlung gratis',
                    'text' => 'Zu jeder Farbbehandlung schenken wir Ihnen eine Intensivpflege.',
                    'price' => '',
                    'old_price' => '',
                    'image' => null,
                    'footer' => '',
                ]],
                ['title' => 'Öffnungszeiten', 'template' => 'info', 'theme' => 'light', 'accent' => 'rose', 'data' => [
                    'heading' => 'Öffnungszeiten',
                    'text' => '',
                    'rows' => self::hours(),
                    'image' => null,
                    'footer' => 'Termin vereinbaren: 01 234 56 78',
                ]],
            ],
            'retail' => [
                ['title' => 'Angebot der Woche', 'template' => 'offer', 'theme' => 'light', 'accent' => 'coral', 'data' => [
                    'kicker' => 'Angebot der Woche',
                    'heading' => 'Alle Wintermäntel',
                    'text' => 'Solange der Vorrat reicht.',
                    'price' => '-30 %',
                    'old_price' => '',
                    'image' => null,
                    'footer' => '',
                ]],
                ['title' => 'Preise', 'template' => 'list', 'theme' => 'dark', 'accent' => 'coral', 'data' => [
                    'heading' => 'Unsere Preise',
                    'subheading' => '',
                    'sections' => [
                        ['title' => 'Service', 'items' => [
                            ['name' => 'Geschenkverpackung', 'description' => '', 'price' => '2,00'],
                            ['name' => 'Änderungsschneiderei', 'description' => 'ab', 'price' => '12,00'],
                            ['name' => 'Lieferung in der Stadt', 'description' => 'Ab 50 € Einkauf gratis', 'price' => '5,00'],
                        ]],
                    ],
                    'footer' => '',
                ]],
                ['title' => 'Öffnungszeiten', 'template' => 'info', 'theme' => 'light', 'accent' => 'coral', 'data' => [
                    'heading' => 'Öffnungszeiten',
                    'text' => '',
                    'rows' => self::hours(),
                    'image' => null,
                    'footer' => '',
                ]],
            ],
            'health' => [
                ['title' => 'Willkommen', 'template' => 'info', 'theme' => 'light', 'accent' => 'blue', 'data' => [
                    'heading' => 'Herzlich willkommen',
                    'text' => "Bitte melden Sie sich an der Rezeption an.\nBitte e-card bereithalten.",
                    'rows' => [],
                    'image' => null,
                    'footer' => '',
                ]],
                ['title' => 'Ordinationszeiten', 'template' => 'info', 'theme' => 'light', 'accent' => 'blue', 'data' => [
                    'heading' => 'Ordinationszeiten',
                    'text' => '',
                    'rows' => [
                        ['label' => 'Montag, Mittwoch', 'value' => '08:00 – 12:00'],
                        ['label' => 'Dienstag, Donnerstag', 'value' => '14:00 – 18:00'],
                        ['label' => 'Freitag', 'value' => '08:00 – 11:00'],
                    ],
                    'image' => null,
                    'footer' => 'Termine nur nach Vereinbarung',
                ]],
                ['title' => 'Leistungen', 'template' => 'list', 'theme' => 'dark', 'accent' => 'blue', 'data' => [
                    'heading' => 'Privatleistungen',
                    'subheading' => '',
                    'sections' => [
                        ['title' => '', 'items' => [
                            ['name' => 'Gesundenuntersuchung erweitert', 'description' => 'Inkl. Laborbefund', 'price' => '120,00'],
                            ['name' => 'Impfberatung', 'description' => 'Reise- und Auffrischungsimpfungen', 'price' => '35,00'],
                        ]],
                    ],
                    'footer' => '',
                ]],
            ],
            'office' => [
                ['title' => 'Willkommen', 'template' => 'info', 'theme' => 'dark', 'accent' => 'blue', 'data' => [
                    'heading' => 'Herzlich willkommen',
                    'text' => 'Schön, dass Sie da sind. WLAN: Gast · Passwort beim Empfang',
                    'rows' => [],
                    'image' => null,
                    'footer' => '',
                ]],
                ['title' => 'Heute im Haus', 'template' => 'list', 'theme' => 'light', 'accent' => 'blue', 'data' => [
                    'heading' => 'Heute im Haus',
                    'subheading' => '',
                    'sections' => [
                        ['title' => '', 'items' => [
                            ['name' => 'Team-Meeting', 'description' => 'Raum Donau', 'price' => '09:00'],
                            ['name' => 'Kundentermin', 'description' => 'Raum Alpen', 'price' => '14:30'],
                        ]],
                    ],
                    'footer' => '',
                ]],
            ],
            'other' => [
                ['title' => 'Willkommen', 'template' => 'info', 'theme' => 'light', 'accent' => 'coral', 'data' => [
                    'heading' => 'Herzlich willkommen',
                    'text' => 'Schön, dass Sie da sind.',
                    'rows' => [],
                    'image' => null,
                    'footer' => '',
                ]],
                ['title' => 'Angebot', 'template' => 'offer', 'theme' => 'dark', 'accent' => 'coral', 'data' => [
                    'kicker' => 'Neu',
                    'heading' => 'Ihr Angebot hier',
                    'text' => 'Ändern Sie diesen Text in wenigen Sekunden.',
                    'price' => '',
                    'old_price' => '',
                    'image' => null,
                    'footer' => '',
                ]],
            ],
        ];
    }
}
