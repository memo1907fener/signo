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

        return match ($template) {
            'list' => [
                'heading' => $s($d['heading'] ?? '', 120),
                'subheading' => $s($d['subheading'] ?? '', 200),
                'sections' => collect(is_array($d['sections'] ?? null) ? $d['sections'] : [])->take(30)->map(fn ($sec) => [
                    'title' => $s($sec['title'] ?? '', 120),
                    'items' => collect(is_array($sec['items'] ?? null) ? $sec['items'] : [])->take(80)->map(fn ($i) => [
                        'name' => $s($i['name'] ?? '', 160),
                        'description' => $s($i['description'] ?? '', 400),
                        'price' => $s($i['price'] ?? '', 40),
                    ])->values()->all(),
                ])->values()->all(),
                'footer' => $s($d['footer'] ?? '', 300),
            ],
            'offer' => [
                'kicker' => $s($d['kicker'] ?? '', 60),
                'heading' => $s($d['heading'] ?? '', 160),
                'text' => $s($d['text'] ?? '', 800),
                'price' => $s($d['price'] ?? '', 40),
                'old_price' => $s($d['old_price'] ?? '', 40),
                'image' => self::path($d['image'] ?? null),
                'footer' => $s($d['footer'] ?? '', 300),
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

    /** Nur eigene Upload-Pfade zulassen. */
    private static function path($p): ?string
    {
        if (! is_string($p) || $p === '') return null;
        return preg_match('#^org/\d+/[A-Za-z0-9._-]+$#', $p) ? $p : null;
    }
}
