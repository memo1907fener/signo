<?php

namespace App\Support;

/** Bereinigt Seiteninhalte: nur bekannte Felder, begrenzte Längen. */
class PageData
{
    public static function clean(string $template, $d): array
    {
        $d = is_array($d) ? $d : [];
        $s = fn ($v, $max = 300) => mb_substr(trim((string) (is_scalar($v) ? $v : '')), 0, $max);
        $base = Starter::blank($template);
        $item = fn ($i) => [
            'name' => $s($i['name'] ?? '', 160),
            'description' => $s($i['description'] ?? '', 400),
            'price' => $s($i['price'] ?? '', 40),
            'tags' => self::tags($i['tags'] ?? []),
            'soldout' => self::day($i['soldout'] ?? ''),
            'happy' => $s($i['happy'] ?? '', 40),
        ];

        return match ($template) {
            'list' => [
                'heading' => $s($d['heading'] ?? '', 120),
                'subheading' => $s($d['subheading'] ?? '', 200),
                'sections' => collect(is_array($d['sections'] ?? null) ? $d['sections'] : [])->take(30)->map(fn ($sec) => [
                    'title' => $s($sec['title'] ?? '', 120),
                    'items' => collect(is_array($sec['items'] ?? null) ? $sec['items'] : [])->take(80)->map($item)->values()->all(),
                ])->values()->all(),
                'footer' => $s($d['footer'] ?? '', 300),
                'ticker' => $s($d['ticker'] ?? '', 400),
                'legend' => (bool) ($d['legend'] ?? true),
                'happy' => self::happy($d['happy'] ?? []),
            ],
            'week' => [
                'heading' => $s($d['heading'] ?? '', 120),
                'subheading' => $s($d['subheading'] ?? '', 200),
                'price' => $s($d['price'] ?? '', 40),
                'view' => in_array($d['view'] ?? '', ['today', 'week'], true) ? $d['view'] : 'today',
                'days' => collect(range(0, 6))->map(fn ($n) => [
                    'items' => collect(is_array($d['days'][$n]['items'] ?? null) ? $d['days'][$n]['items'] : [])->take(12)->map($item)->values()->all(),
                ])->all(),
                'footer' => $s($d['footer'] ?? '', 300),
                'ticker' => $s($d['ticker'] ?? '', 400),
                'legend' => (bool) ($d['legend'] ?? true),
            ],
            'offer' => [
                'kicker' => $s($d['kicker'] ?? '', 60),
                'heading' => $s($d['heading'] ?? '', 160),
                'text' => $s($d['text'] ?? '', 800),
                'price' => $s($d['price'] ?? '', 40),
                'old_price' => $s($d['old_price'] ?? '', 40),
                'image' => self::path($d['image'] ?? null),
                'footer' => $s($d['footer'] ?? '', 300),
                'ticker' => $s($d['ticker'] ?? '', 400),
            ],
            'info' => [
                'heading' => $s($d['heading'] ?? '', 160),
                'text' => $s($d['text'] ?? '', 1500),
                'rows' => collect(is_array($d['rows'] ?? null) ? $d['rows'] : [])->take(30)->map(fn ($r) => [
                    'label' => $s($r['label'] ?? '', 120),
                    'value' => $s($r['value'] ?? '', 120),
                ])->values()->all(),
                'image' => self::path($d['image'] ?? null),
                'footer' => $s($d['footer'] ?? '', 300),
                'ticker' => $s($d['ticker'] ?? '', 400),
            ],
            'clock' => [
                'heading' => $s($d['heading'] ?? '', 120),
                'text' => $s($d['text'] ?? '', 300),
                'city' => $s($d['city'] ?? '', 80),
                'weather' => (bool) ($d['weather'] ?? false),
            ],
            'countdown' => [
                'heading' => $s($d['heading'] ?? '', 120),
                'text' => $s($d['text'] ?? '', 300),
                'target' => preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string) ($d['target'] ?? '')) ? $d['target'] : '',
                'done_text' => $s($d['done_text'] ?? '', 120),
            ],
            'media' => [
                'file' => self::path($d['file'] ?? null),
                'mime' => $s($d['mime'] ?? '', 60) ?: null,
                'fit' => in_array($d['fit'] ?? '', ['cover', 'contain'], true) ? $d['fit'] : 'cover',
                'caption' => $s($d['caption'] ?? '', 160),
            ],
            default => $base,
        };
    }

    /** Allergen-Buchstaben und Kennzeichen: nur bekannte, ohne Doppelte, in fester Reihenfolge. */
    public static function tags($tags): array
    {
        $known = array_merge(array_keys(config('signo.allergens')), array_keys(config('signo.marks')));
        $tags = array_map(fn ($t) => is_string($t) ? (strlen($t) === 1 ? strtoupper($t) : strtolower($t)) : '', is_array($tags) ? $tags : []);
        return array_values(array_intersect($known, $tags));
    }

    /** "Ausverkauft" gilt für einen Tag: Datum behalten, solange es nicht vergangen ist. */
    private static function day($v): string
    {
        if ($v === true) return now()->format('Y-m-d');
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && $v >= now()->format('Y-m-d') ? $v : '';
    }

    /** Happy Hour: Zeitfenster, in dem die Aktionspreise gelten. */
    private static function happy($h): array
    {
        $h = is_array($h) ? $h : [];
        $time = fn ($v) => is_string($v) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : '';
        $days = array_values(array_unique(array_filter(array_map('intval', (array) ($h['days'] ?? [])), fn ($d) => $d >= 1 && $d <= 7)));
        sort($days);
        return [
            'enabled' => (bool) ($h['enabled'] ?? false),
            'label' => mb_substr(trim((string) (is_scalar($h['label'] ?? '') ? ($h['label'] ?? '') : '')), 0, 40) ?: 'Happy Hour',
            'days' => $days ?: [1, 2, 3, 4, 5, 6, 7],
            'start' => $time($h['start'] ?? ''),
            'end' => $time($h['end'] ?? ''),
        ];
    }

    /** Nur eigene Upload-Pfade zulassen. */
    private static function path($p): ?string
    {
        if (! is_string($p) || $p === '') return null;
        return preg_match('#^org/\d+/[A-Za-z0-9._-]+$#', $p) ? $p : null;
    }
}
