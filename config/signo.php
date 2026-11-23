<?php

/*
| Signo – zentrale Einstellungen
| Alles, was Kunden sehen (Branchen, Vorlagen, Pakete), steht hier an EINER Stelle.
*/

return [

    'trial_days' => (int) env('SIGNO_TRIAL_DAYS', 14),

    // Wer sich mit dieser Adresse registriert, wird Plattform-Admin
    'admin_emails' => array_filter(array_map('trim', explode(',', (string) env('SIGNO_ADMIN_EMAILS', '')))),

    'company' => [
        'name'    => env('SIGNO_COMPANY_NAME', 'Signo'),
        'address' => env('SIGNO_COMPANY_ADDRESS', ''),
        'email'   => env('SIGNO_COMPANY_EMAIL', 'hallo@signo.at'),
        'phone'   => env('SIGNO_COMPANY_PHONE', ''),
        'uid'     => env('SIGNO_COMPANY_UID', ''),
        'register'=> env('SIGNO_COMPANY_REGISTER', ''),
    ],

    'industries' => [
        'gastro' => ['label' => 'Gastronomie & Café', 'icon' => '🍽️'],
        'beauty' => ['label' => 'Friseur & Kosmetik', 'icon' => '✂️'],
        'retail' => ['label' => 'Handel & Geschäft', 'icon' => '🛍️'],
        'health' => ['label' => 'Praxis & Gesundheit', 'icon' => '🩺'],
        'office' => ['label' => 'Büro, Hotel & Empfang', 'icon' => '🏢'],
        'other'  => ['label' => 'Etwas anderes', 'icon' => '✨'],
    ],

    // Vorlagentypen – bewusst wenige, dafür universell
    'templates' => [
        'list'  => ['label' => 'Liste mit Preisen', 'hint' => 'Speisekarte, Preisliste, Leistungen'],
        'offer' => ['label' => 'Angebot',           'hint' => 'Tagesangebot, Aktion, Neuheit'],
        'info'  => ['label' => 'Information',        'hint' => 'Willkommen, Öffnungszeiten, Hinweis'],
        'media' => ['label' => 'Bild oder Video',    'hint' => 'Plakat, Foto, Werbespot'],
        'clock' => ['label' => 'Uhr & Wetter',       'hint' => 'Große Uhr, Datum und Wetter vor Ort'],
        'countdown' => ['label' => 'Countdown',      'hint' => 'Eröffnung, Silvester, Aktionsende'],
        'week'  => ['label' => 'Wochenmenü',         'hint' => 'Mittagsmenü Montag bis Freitag, zeigt automatisch den richtigen Tag'],
        'orders' => ['label' => 'Abholanzeige',      'hint' => 'Bestellnummern für Gäste', 'hidden' => true],
    ],

    // Akzentfarben zur Auswahl
    'accents' => [
        'coral'  => '#ff5a36',
        'gold'   => '#d4a017',
        'green'  => '#2f9e62',
        'blue'   => '#2f6fed',
        'plum'   => '#8e44ad',
        'rose'   => '#e0457b',
    ],

    // Designs (in der Datenbank weiterhin im Feld "font")
    'fonts' => [
        'modern'  => 'Modern',
        'elegant' => 'Elegant',
        'bistro'  => 'Bistro',
        'chalk'   => 'Kreidetafel',
        'minimal' => 'Minimal',
        'neon'    => 'Neon',
    ],

    // Die 14 kennzeichnungspflichtigen Allergene (Buchstaben nach österreichischer Empfehlung)
    'allergens' => [
        'A' => 'Gluten', 'B' => 'Krebstiere', 'C' => 'Ei', 'D' => 'Fisch', 'E' => 'Erdnuss', 'F' => 'Soja', 'G' => 'Milch',
        'H' => 'Schalenfrüchte', 'L' => 'Sellerie', 'M' => 'Senf', 'N' => 'Sesam', 'O' => 'Sulfite', 'P' => 'Lupinen', 'R' => 'Weichtiere',
    ],

    // Zusätzliche Kennzeichen je Eintrag
    'marks' => [
        'vegan' => ['label' => 'Vegan', 'icon' => '🌱'],
        'veg'   => ['label' => 'Vegetarisch', 'icon' => '🥕'],
        'hot'   => ['label' => 'Scharf', 'icon' => '🌶️'],
        'halal' => ['label' => 'Halal', 'icon' => '☪️'],
        'bio'   => ['label' => 'Bio', 'icon' => '🍃'],
        'new'   => ['label' => 'Neu', 'icon' => '⭐'],
    ],

    // Minuten ohne Lebenszeichen, bis der Ausfall-Alarm verschickt wird
    'offline_minutes' => (int) env('SIGNO_OFFLINE_MINUTES', 15),

    // Pakete – Preise in Cent; Stripe-Preis-IDs aus der .env
    'plans' => [
        'qr' => [
            'name' => 'QR & Web',
            'price' => 2900, 'interval' => 'year',
            'screens' => 0,
            'stripe_price' => env('STRIPE_PRICE_QR'),
            'features' => ['Alle Vorlagen hell & dunkel', 'KI-Import aus Foto oder PDF', 'QR-Code & Handy-Seite', 'Automatisch in 16 Sprachen', 'Allergene, Ausverkauft & Happy Hour', 'Bestellen per Handy mit Küchenansicht', 'Feedback & Stempelkarte', 'Statistik'],
        ],
        'screen' => [
            'name' => 'Bildschirm',
            'price' => 1900, 'interval' => 'month',
            'screens' => 1,
            'stripe_price' => env('STRIPE_PRICE_SCREEN'),
            'features' => ['Alles aus QR & Web', '1 Bildschirm', 'Wochenmenü & Eilmeldung am Bildschirm', 'Abholanzeige für Bestellungen', 'Betriebszeiten & Ausfall-Alarm', 'Offline-Wiedergabe'],
        ],
        'multi' => [
            'name' => 'Mehrere Bildschirme',
            'price' => 3900, 'interval' => 'month',
            'screens' => 5,
            'stripe_price' => env('STRIPE_PRICE_MULTI'),
            'features' => ['Alles aus Bildschirm', 'Bis zu 5 Bildschirme', 'Unterschiedliche Inhalte je Bildschirm', 'Bevorzugter Support'],
        ],
    ],

    // Während der Testphase erlaubt
    'trial_screens' => 2,

    'ai' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5-5'),
        'url' => env('SIGNO_AI_URL', 'https://api.anthropic.com'),
        // Abtippen braucht wenig Nachdenken: "low" ist schneller und günstiger
        'effort' => env('ANTHROPIC_EFFORT', 'low'),
        'daily_limit' => (int) env('SIGNO_AI_DAILY_LIMIT', 20),
    ],

    // Fotobibliothek (kostenloser Schlüssel: https://www.pexels.com/api/)
    'pexels' => env('PEXELS_API_KEY'),

    'stripe' => [
        'secret'  => env('STRIPE_SECRET'),
        'webhook' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
