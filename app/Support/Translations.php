<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Page;
use App\Services\AiImport;
use Illuminate\Support\Arr;

/**
 * Mehrsprachige Seiten: Nur Texte werden übersetzt, Preise, Bilder und Aufbau bleiben gleich.
 * Gespeichert wird je Sprache eine Liste "Pfad => Text", z. B. "sections.0.items.2.name".
 */
class Translations
{
    public const LANGUAGES = [
        'en' => ['English', 'EN'],
        'it' => ['Italiano', 'IT'],
        'fr' => ['Français', 'FR'],
        'es' => ['Español', 'ES'],
        'nl' => ['Nederlands', 'NL'],
        'cs' => ['Čeština', 'CS'],
        'sk' => ['Slovenčina', 'SK'],
        'hu' => ['Magyar', 'HU'],
        'sl' => ['Slovenščina', 'SL'],
        'hr' => ['Hrvatski', 'HR'],
        'pl' => ['Polski', 'PL'],
        'tr' => ['Türkçe', 'TR'],
        'uk' => ['Українська', 'UK'],
        'ru' => ['Русский', 'RU'],
        'zh' => ['中文', 'ZH'],
        'ja' => ['日本語', 'JA'],
    ];

    /** Alle übersetzbaren Texte einer Seite als "Pfad => Text". */
    public static function strings(string $template, array $d): array
    {
        $out = [];
        $add = function (string $path) use (&$out, $d) {
            $v = Arr::get($d, $path);
            if (is_string($v) && trim($v) !== '' && preg_match('/\p{L}/u', $v)) $out[$path] = $v;
        };
        foreach (['heading', 'subheading', 'kicker', 'text', 'footer', 'caption', 'ticker', 'done_text'] as $k) $add($k);
        foreach (($d['sections'] ?? []) as $s => $sec) {
            $add("sections.$s.title");
            foreach (($sec['items'] ?? []) as $i => $_) {
                $add("sections.$s.items.$i.name");
                $add("sections.$s.items.$i.description");
            }
        }
        foreach (($d['days'] ?? []) as $n => $day) {
            foreach (($day['items'] ?? []) as $i => $_) {
                $add("days.$n.items.$i.name");
                $add("days.$n.items.$i.description");
            }
        }
        $add('happy.label');
        foreach (($d['rows'] ?? []) as $r => $_) {
            $add("rows.$r.label");
            $add("rows.$r.value");
        }
        return $out;
    }

    public static function hash(string $template, array $d): string
    {
        return md5(json_encode(self::strings($template, $d)));
    }

    /** Übersetzung über die Originaldaten legen – nur wo der deutsche Text noch derselbe ist. */
    public static function apply(string $template, array $d, array $map): array
    {
        foreach ($map as $path => $entry) {
            if (! is_array($entry) || ! isset($entry['s'], $entry['t'])) continue;
            if (Arr::get($d, $path) === $entry['s']) Arr::set($d, $path, mb_substr((string) $entry['t'], 0, 500));
        }
        return $d;
    }

    /** Welche Texte fehlen je Sprache noch (neu oder geändert)? */
    public static function missing(Page $page): array
    {
        $strings = self::strings($page->template, $page->data ?? []);
        $out = [];
        foreach (($page->languages ?? []) as $l) {
            $have = $page->translations[$l] ?? [];
            $out[$l] = array_keys(array_filter($strings, fn ($text, $path) => ($have[$path]['s'] ?? null) !== $text, ARRAY_FILTER_USE_BOTH));
        }
        return $out;
    }

    public static function missingCount(Page $page): int
    {
        return (int) collect(self::missing($page))->map(fn ($a) => count($a))->max();
    }

    /** Fehlende Texte in alle gewählten Sprachen übersetzen (ein KI-Aufruf für alle Sprachen). */
    public static function translate(Organization $org, Page $page): void
    {
        $langs = array_values(array_intersect($page->languages ?? [], array_keys(self::LANGUAGES)));
        $strings = self::strings($page->template, $page->data ?? []);

        // Nur behalten, was zu aktuellen Texten passt; abgewählte Sprachen entfernen
        $translations = [];
        foreach ($langs as $l) {
            $translations[$l] = array_filter($page->translations[$l] ?? [], fn ($e, $path) => isset($strings[$path]) && ($e['s'] ?? null) === $strings[$path], ARRAY_FILTER_USE_BOTH);
        }

        $todo = [];
        foreach ($langs as $l) foreach ($strings as $path => $text) if (! isset($translations[$l][$path])) $todo[$path] = $text;

        if ($todo) {
            $ids = array_keys($todo);
            $list = [];
            foreach ($ids as $n => $path) $list[] = ['id' => $n, 'text' => $todo[$path]];
            $names = implode(', ', array_map(fn ($l) => "$l (" . self::LANGUAGES[$l][0] . ')', $langs));
            $prompt = "Übersetze die folgenden Texte einer Anzeige ({$org->industryLabel()}) aus dem Deutschen in diese Sprachen: $names.\n"
                . "Regeln:\n"
                . "- Natürlich und kurz wie auf einer echten Speise-/Preiskarte, keine wörtlichen Holper-Übersetzungen.\n"
                . "- Österreichische Spezialitäten (z. B. Wiener Schnitzel, Kaiserschmarrn, Tafelspitz) als Eigennamen behalten und bei Bedarf kurz erklären.\n"
                . "- Uhrzeiten, Zahlen, Allergen-Buchstaben und Markennamen unverändert lassen.\n"
                . "- Jede id genau einmal je Sprache zurückgeben.\n\n"
                . json_encode($list, JSON_UNESCAPED_UNICODE);

            $schema = [
                'type' => 'object',
                'properties' => [
                    'languages' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'lang' => ['type' => 'string', 'enum' => $langs],
                                'texts' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => ['id' => ['type' => 'integer'], 'text' => ['type' => 'string']],
                                        'required' => ['id', 'text'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                            ],
                            'required' => ['lang', 'texts'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['languages'],
                'additionalProperties' => false,
            ];

            $out = AiImport::request($org, [['type' => 'text', 'text' => $prompt]], $schema, min(64000, 2000 + count($ids) * 60 * count($langs)));

            foreach (($out['languages'] ?? []) as $block) {
                $l = strtolower((string) ($block['lang'] ?? ''));
                if (! in_array($l, $langs, true)) continue;
                foreach (($block['texts'] ?? []) as $t) {
                    $n = (int) ($t['id'] ?? -1);
                    if (isset($ids[$n]) && is_string($t['text'] ?? null) && trim($t['text']) !== '') {
                        $translations[$l][$ids[$n]] = ['s' => $todo[$ids[$n]], 't' => trim($t['text'])];
                    }
                }
            }
        }

        $page->update([
            'translations' => $translations,
            'translated_hash' => self::hash($page->template, $page->data ?? []),
        ]);
    }

    /** Bevorzugte Sprache des Besuchers aus dem Browser. */
    public static function fromBrowser(?string $header, array $available): ?string
    {
        foreach (explode(',', (string) $header) as $part) {
            $code = strtolower(substr(trim($part), 0, 2));
            if ($code === 'de') return null;
            if (in_array($code, $available, true)) return $code;
        }
        return null;
    }
}
