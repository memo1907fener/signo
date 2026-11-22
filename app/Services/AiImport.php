<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * KI-Import: Foto, PDF oder Text → fertige Signo-Seite.
 * Nutzt die Claude-API (Schlüssel in .env: ANTHROPIC_API_KEY).
 */
class AiImport
{
    public static function available(): bool
    {
        return filled(config('signo.ai.key'));
    }

    public static function remainingToday(Organization $org): int
    {
        $used = (int) Cache::get(self::counterKey($org), 0);
        return max(0, config('signo.ai.daily_limit') - $used);
    }

    private static function counterKey(Organization $org): string
    {
        return 'ai:' . $org->id . ':' . now()->format('Ymd');
    }

    /**
     * @return array{template:string,title:string,data:array}
     */
    public static function run(Organization $org, ?UploadedFile $file, string $text): array
    {
        if (! self::available()) {
            throw new RuntimeException('Der KI-Import ist noch nicht eingerichtet.');
        }
        if (self::remainingToday($org) <= 0) {
            throw new RuntimeException('Das Tageslimit für KI-Importe ist erreicht. Morgen geht es wieder.');
        }

        $content = [];
        if ($file) $content[] = self::fileBlock($file);
        $industry = $org->industryLabel();
        $content[] = ['type' => 'text', 'text' => self::prompt($industry, $text)];

        $res = Http::withHeaders([
            'x-api-key' => config('signo.ai.key'),
            'anthropic-version' => '2023-06-01',
        ])->timeout(150)->connectTimeout(15)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('signo.ai.model'),
            'max_tokens' => 16000,
            'messages' => [['role' => 'user', 'content' => $content]],
            'output_config' => array_filter([
                'effort' => config('signo.ai.effort') ?: null,
                'format' => ['type' => 'json_schema', 'schema' => self::schema()],
            ]),
        ]);

        if (! $res->successful()) {
            Log::warning('KI-Import fehlgeschlagen', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 1000)]);
            $msg = match (true) {
                $res->status() === 401 => 'Der KI-Schlüssel ist ungültig. Bitte in der .env prüfen.',
                $res->status() === 429 || $res->status() === 529 => 'Die KI ist gerade ausgelastet. Bitte in einer Minute erneut versuchen.',
                $res->status() === 413 => 'Die Datei ist zu groß.',
                default => 'Die KI konnte die Datei nicht verarbeiten (Fehler ' . $res->status() . ').',
            };
            throw new RuntimeException($msg);
        }

        Cache::put(self::counterKey($org), (int) Cache::get(self::counterKey($org), 0) + 1, now()->endOfDay());

        if ($res->json('stop_reason') === 'max_tokens') {
            throw new RuntimeException('Die Vorlage ist sehr umfangreich. Bitte in zwei Teilen hochladen (z. B. Speisen und Getränke getrennt).');
        }
        $json = collect($res->json('content', []))->firstWhere('type', 'text')['text'] ?? '';
        $out = json_decode($json, true);
        if (! is_array($out)) {
            if (preg_match('/\{.*\}/s', $json, $m)) $out = json_decode($m[0], true);
        }
        if (! is_array($out)) {
            throw new RuntimeException('Die KI hat keine verwertbaren Inhalte gefunden.');
        }
        if (($out['readable'] ?? true) === false) {
            throw new RuntimeException('Auf der Datei war leider nichts Lesbares zu erkennen. Bitte ein schärferes Foto verwenden.');
        }

        return self::map($out);
    }

    private static function prompt(string $industry, string $text): string
    {
        $p = "Du hilfst einem Betrieb (Branche: {$industry}), eine Anzeige für einen Bildschirm im Geschäft zu erstellen.\n"
           . "Lies die beigefügte Datei und/oder den Text und übertrage den Inhalt vollständig und wortgetreu in das JSON-Format.\n\n"
           . "Regeln:\n"
           . "- template = \"list\" für Speisekarten, Preislisten, Leistungen, Programme (viele Einträge mit Preis oder Uhrzeit).\n"
           . "- template = \"offer\" für ein einzelnes Angebot, eine Aktion oder ein Tagesgericht.\n"
           . "- template = \"info\" für Willkommen, Öffnungszeiten, Hinweise (rows = Tabelle aus Bezeichnung und Wert).\n"
           . "- Übernimm Namen, Beschreibungen und Preise exakt. Erfinde nichts dazu, lasse nichts weg.\n"
           . "- Preise im österreichischen Format ohne Währungszeichen, z. B. \"12,90\". Steht \"ab\" davor, behalte es.\n"
           . "- Gruppiere Einträge in sections mit passender Überschrift, wenn die Vorlage Gruppen hat.\n"
           . "- Allergen-Buchstaben dürfen in der Beschreibung bleiben.\n"
           . "- Sprache der Vorlage beibehalten. Korrigiere offensichtliche Tippfehler.\n"
           . "- title = kurzer interner Name für die Seite, z. B. \"Speisekarte\".\n"
           . "- Nicht benutzte Felder als leere Zeichenkette bzw. leere Liste.\n"
           . "- readable = false, wenn nichts Sinnvolles erkennbar ist.";
        if (trim($text) !== '') {
            $p .= "\n\nText des Kunden:\n\"\"\"\n" . mb_substr($text, 0, 20000) . "\n\"\"\"";
        }
        return $p;
    }

    private static function schema(): array
    {
        $str = ['type' => 'string'];
        return [
            'type' => 'object',
            'properties' => [
                'readable' => ['type' => 'boolean'],
                'template' => ['type' => 'string', 'enum' => ['list', 'offer', 'info']],
                'title' => $str,
                'heading' => $str,
                'subheading' => $str,
                'kicker' => $str,
                'text' => $str,
                'price' => $str,
                'old_price' => $str,
                'footer' => $str,
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => $str,
                            'items' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => ['name' => $str, 'description' => $str, 'price' => $str],
                                    'required' => ['name', 'description', 'price'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['title', 'items'],
                        'additionalProperties' => false,
                    ],
                ],
                'rows' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => ['label' => $str, 'value' => $str],
                        'required' => ['label', 'value'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['readable', 'template', 'title', 'heading', 'subheading', 'kicker', 'text', 'price', 'old_price', 'footer', 'sections', 'rows'],
            'additionalProperties' => false,
        ];
    }

    public static function map(array $o): array
    {
        $s = fn ($k) => trim((string) ($o[$k] ?? ''));
        $template = in_array($o['template'] ?? '', ['list', 'offer', 'info'], true) ? $o['template'] : 'list';
        $data = match ($template) {
            'list' => [
                'heading' => $s('heading') ?: $s('title'),
                'subheading' => $s('subheading'),
                'sections' => collect($o['sections'] ?? [])->map(fn ($sec) => [
                    'title' => trim((string) ($sec['title'] ?? '')),
                    'items' => collect($sec['items'] ?? [])->map(fn ($i) => [
                        'name' => trim((string) ($i['name'] ?? '')),
                        'description' => trim((string) ($i['description'] ?? '')),
                        'price' => trim((string) ($i['price'] ?? '')),
                    ])->values()->all(),
                ])->values()->all() ?: [['title' => '', 'items' => [['name' => '', 'description' => '', 'price' => '']]]],
                'footer' => $s('footer'),
            ],
            'offer' => [
                'kicker' => $s('kicker'),
                'heading' => $s('heading') ?: $s('title'),
                'text' => $s('text'),
                'price' => $s('price'),
                'old_price' => $s('old_price'),
                'image' => null,
                'footer' => $s('footer'),
            ],
            'info' => [
                'heading' => $s('heading') ?: $s('title'),
                'text' => $s('text'),
                'rows' => collect($o['rows'] ?? [])->map(fn ($r) => [
                    'label' => trim((string) ($r['label'] ?? '')),
                    'value' => trim((string) ($r['value'] ?? '')),
                ])->values()->all(),
                'image' => null,
                'footer' => $s('footer'),
            ],
        };

        return ['template' => $template, 'title' => mb_substr($s('title') ?: 'Neue Seite', 0, 80), 'data' => $data];
    }

    private static function fileBlock(UploadedFile $file): array
    {
        $mime = $file->getMimeType();
        $bytes = file_get_contents($file->getRealPath());

        if ($mime === 'application/pdf') {
            return ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($bytes)]];
        }

        // Bilder: große Handyfotos verkleinern (schneller und günstiger)
        [$bytes, $mime] = self::shrink($bytes, $mime);
        return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode($bytes)]];
    }

    private static function shrink(string $bytes, string $mime): array
    {
        if (! function_exists('imagecreatefromstring')) return [$bytes, $mime];
        $img = @imagecreatefromstring($bytes);
        if (! $img) return [$bytes, $mime];
        // Handyfotos: Ausrichtung aus EXIF übernehmen
        $orientation = 1;
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
            $orientation = (int) ($exif['Orientation'] ?? 1);
            $rot = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($rot) { $r2 = imagerotate($img, $rot, 0); if ($r2) { imagedestroy($img); $img = $r2; } }
        }
        $w = imagesx($img); $h = imagesy($img);
        $max = 2000;
        if ($orientation === 1 && $w <= $max && $h <= $max && strlen($bytes) < 3_500_000) { imagedestroy($img); return [$bytes, $mime]; }
        $r = min($max / $w, $max / $h, 1);
        $nw = (int) round($w * $r); $nh = (int) round($h * $r);
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start(); imagejpeg($dst, null, 85); $out = ob_get_clean();
        imagedestroy($img); imagedestroy($dst);
        return [$out, 'image/jpeg'];
    }
}
