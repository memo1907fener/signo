<?php

namespace App\Services;

use App\Models\Page;
use App\Support\PageData;
use RuntimeException;

/**
 * Änderungen in Alltagssprache: "Schnitzel kostet jetzt 19,90 und Gulasch streichen".
 * Die KI liefert die geänderte Seite zurück; der Kunde sieht vorher, was sich ändert.
 */
class VoiceEdit
{
    public static function run(Page $page, string $command): array
    {
        $command = trim(mb_substr($command, 0, 2000));
        if ($command === '') throw new RuntimeException('Bitte sagen oder schreiben Sie, was sich ändern soll.');
        if ($page->template === 'media') throw new RuntimeException('Bei Bild- und Videoseiten gibt es nichts zu ändern – bitte die Datei austauschen.');

        $current = PageData::clean($page->template, $page->data);
        $editable = self::editable($page->template, $current);

        $prompt = "Hier ist der aktuelle Inhalt einer Anzeige als JSON:\n" . json_encode($editable, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nÄnderungswunsch des Betreibers (eventuell per Spracherkennung, also mit Tippfehlern):\n\"\"\"\n$command\n\"\"\"\n\n"
            . "Gib den kompletten Inhalt mit den gewünschten Änderungen zurück.\n"
            . "Regeln:\n"
            . "- Ändere NUR, was verlangt wird. Alles andere bleibt Zeichen für Zeichen gleich, auch die Reihenfolge.\n"
            . "- Einträge sind gemeint, auch wenn der Name nur ungefähr genannt wird (\"Schnitzel\" = \"Wiener Schnitzel\").\n"
            . "- Preise im Format \"12,90\" (Komma, ohne Euro-Zeichen), außer der bisherige Preis hat ein anderes Format.\n"
            . "- Neue Einträge an passender Stelle einfügen; \"streichen\", \"löschen\", \"entfernen\" bedeutet Eintrag entfernen.\n"
            . "- \"ist aus\", \"ausverkauft\", \"gibt es heute nicht mehr\" bedeutet sold_out = true (Eintrag bleibt stehen); \"wieder da\" bedeutet sold_out = false.\n"
            . "- Beim Wochenmenü hat days genau 7 Einträge in der Reihenfolge Montag bis Sonntag; diese Reihenfolge und Anzahl nie ändern.\n"
            . "- understood = false, wenn der Wunsch unklar ist oder nichts zu ändern ist; question = kurze Rückfrage auf Deutsch.";

        $schema = [
            'type' => 'object',
            'properties' => [
                'understood' => ['type' => 'boolean'],
                'question' => ['type' => 'string'],
                'content' => self::schema($page->template),
            ],
            'required' => ['understood', 'question', 'content'],
            'additionalProperties' => false,
        ];

        $out = AiImport::request($page->organization, [['type' => 'text', 'text' => $prompt]], $schema, 16000);

        if (! ($out['understood'] ?? false)) {
            throw new RuntimeException(trim((string) ($out['question'] ?? '')) ?: 'Das habe ich nicht verstanden. Bitte anders formulieren.');
        }

        // Bilder/Dateien bleiben unangetastet; Kennzeichen und Aktionspreise wandern mit dem Eintrag mit
        $new = array_merge($current, self::restore($current, $out['content'] ?? []));
        foreach (['image', 'file', 'mime', 'fit'] as $k) if (array_key_exists($k, $current)) $new[$k] = $current[$k];
        $new = PageData::clean($page->template, $new);

        $changes = self::diff($page->template, $current, $new);
        if (! $changes) throw new RuntimeException('Es wurde nichts geändert. Bitte genauer sagen, was sich ändern soll.');

        return ['data' => $new, 'changes' => $changes];
    }

    private static function editable(string $template, array $d): array
    {
        unset($d['image'], $d['file'], $d['mime'], $d['fit'], $d['happy'], $d['legend'], $d['ticker'], $d['view']);
        $slim = fn ($it) => ['name' => $it['name'] ?? '', 'description' => $it['description'] ?? '', 'price' => $it['price'] ?? '', 'sold_out' => Page::soldOut($it)];
        foreach (['sections', 'days'] as $g) {
            foreach (($d[$g] ?? []) as $n => $group) {
                $d[$g][$n]['items'] = array_map($slim, $group['items'] ?? []);
                if ($g === 'days') $d[$g][$n] = ['day' => \App\Support\Ui::weekday($n, 'de')] + $d[$g][$n];
            }
        }
        return $d;
    }

    /** Antwort der KI zurück ins Speicherformat: Kennzeichen, Aktionspreis und "ausverkauft" je Eintrag. */
    private static function restore(array $current, array $content): array
    {
        $known = [];
        foreach (['sections', 'days'] as $g) foreach (($current[$g] ?? []) as $group) foreach (($group['items'] ?? []) as $it) {
            $known[mb_strtolower(trim($it['name'] ?? ''))] = $it;
        }
        $today = now()->timezone(config('app.timezone'))->format('Y-m-d');
        foreach (['sections', 'days'] as $g) {
            if (! isset($content[$g]) || ! is_array($content[$g])) continue;
            if ($g === 'days') $content[$g] = array_slice(array_values($content[$g]), 0, 7);
            foreach ($content[$g] as $n => $group) {
                unset($content[$g][$n]['day']);
                $content[$g][$n]['items'] = array_map(function ($it) use ($known, $today) {
                    $old = $known[mb_strtolower(trim($it['name'] ?? ''))] ?? [];
                    $it['tags'] = $old['tags'] ?? [];
                    $it['happy'] = $old['happy'] ?? '';
                    $it['soldout'] = ! empty($it['sold_out']) ? $today : '';
                    unset($it['sold_out']);
                    return $it;
                }, is_array($group['items'] ?? null) ? $group['items'] : []);
            }
        }
        return $content;
    }

    private static function schema(string $template): array
    {
        $s = ['type' => 'string'];
        $obj = fn (array $props) => ['type' => 'object', 'properties' => $props, 'required' => array_keys($props), 'additionalProperties' => false];
        $items = ['type' => 'array', 'items' => $obj(['name' => $s, 'description' => $s, 'price' => $s, 'sold_out' => ['type' => 'boolean']])];
        return match ($template) {
            'list' => $obj([
                'heading' => $s, 'subheading' => $s, 'footer' => $s,
                'sections' => ['type' => 'array', 'items' => $obj(['title' => $s, 'items' => $items])],
            ]),
            'week' => $obj([
                'heading' => $s, 'subheading' => $s, 'price' => $s, 'footer' => $s,
                'days' => ['type' => 'array', 'items' => $obj(['day' => $s, 'items' => $items])],
            ]),
            'clock' => $obj(['heading' => $s, 'text' => $s, 'city' => $s]),
            'countdown' => $obj(['heading' => $s, 'text' => $s, 'done_text' => $s]),
            'offer' => $obj(['kicker' => $s, 'heading' => $s, 'text' => $s, 'price' => $s, 'old_price' => $s, 'footer' => $s]),
            'info' => $obj([
                'heading' => $s, 'text' => $s, 'footer' => $s,
                'rows' => ['type' => 'array', 'items' => $obj(['label' => $s, 'value' => $s])],
            ]),
            default => $obj(['caption' => $s]),
        };
    }

    /** Verständliche Liste der Änderungen für die Vorschau. */
    public static function diff(string $template, array $old, array $new): array
    {
        $out = [];
        $labels = ['heading' => 'Überschrift', 'subheading' => 'Untertitel', 'kicker' => 'Kleine Überschrift', 'text' => 'Text',
            'price' => 'Preis', 'old_price' => 'Alter Preis', 'footer' => 'Fußzeile', 'caption' => 'Beschriftung'];
        foreach ($labels as $k => $label) {
            if (($old[$k] ?? '') !== ($new[$k] ?? '') && (isset($old[$k]) || isset($new[$k]))) {
                $out[] = ['type' => 'change', 'text' => "$label: „" . ($old[$k] ?? '') . "“ → „" . ($new[$k] ?? '') . "“"];
            }
        }

        if ($template === 'list' || $template === 'week') {
            $flat = function (array $d) use ($template) {
                $r = [];
                foreach (($d[$template === 'week' ? 'days' : 'sections'] ?? []) as $n => $sec) foreach (($sec['items'] ?? []) as $it) {
                    if (trim($it['name'] ?? '') === '') continue;
                    // Im Wochenmenü darf dasselbe Gericht an mehreren Tagen stehen
                    $r[($template === 'week' ? $n . '|' : '') . mb_strtolower(trim($it['name']))] = $it + ['section' => $sec['title'] ?? ''];
                }
                return $r;
            };
            $a = $flat($old); $b = $flat($new);
            foreach ($b as $key => $it) {
                if (! isset($a[$key])) {
                    $out[] = ['type' => 'add', 'text' => "Neu: {$it['name']}" . ($it['price'] !== '' ? " – {$it['price']}" : '')];
                } else {
                    if (($a[$key]['price'] ?? '') !== ($it['price'] ?? '')) $out[] = ['type' => 'change', 'text' => "{$it['name']}: {$a[$key]['price']} → {$it['price']}"];
                    if (($a[$key]['description'] ?? '') !== ($it['description'] ?? '')) $out[] = ['type' => 'change', 'text' => "{$it['name']}: Beschreibung geändert"];
                    if (Page::soldOut($a[$key]) !== Page::soldOut($it)) $out[] = ['type' => 'change', 'text' => $it['name'] . (Page::soldOut($it) ? ': heute ausverkauft' : ': wieder verfügbar')];
                }
            }
            foreach ($a as $key => $it) if (! isset($b[$key])) $out[] = ['type' => 'remove', 'text' => "Entfernt: {$it['name']}"];
            $titles = fn ($d) => array_map(fn ($s) => $s['title'] ?? '', $d['sections'] ?? []);
            if ($titles($old) !== $titles($new) && count($out) === 0) $out[] = ['type' => 'change', 'text' => 'Gruppen geändert'];
            if (! $out && json_encode($old) !== json_encode($new)) $out[] = ['type' => 'change', 'text' => 'Reihenfolge oder Schreibweise geändert'];
        }

        if ($template === 'info' && json_encode($old['rows'] ?? []) !== json_encode($new['rows'] ?? [])) {
            $fmt = fn ($rows) => array_map(fn ($r) => trim(($r['label'] ?? '') . ': ' . ($r['value'] ?? '')), $rows);
            $o = $fmt($old['rows'] ?? []); $n = $fmt($new['rows'] ?? []);
            foreach (array_diff($n, $o) as $t) $out[] = ['type' => 'add', 'text' => $t];
            foreach (array_diff($o, $n) as $t) $out[] = ['type' => 'remove', 'text' => $t];
        }

        return $out;
    }
}
