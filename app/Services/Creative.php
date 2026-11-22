<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Gestaltungshilfe: Textvorschläge per KI und Fotos aus der Pexels-Bibliothek. */
class Creative
{
    public static function photosAvailable(): bool
    {
        return filled(config('signo.pexels'));
    }

    /** Drei Textvorschläge für ein Angebot oder einen Hinweis. */
    public static function ideas(Organization $org, string $template, string $idea): array
    {
        $idea = trim(mb_substr($idea, 0, 300));
        if ($idea === '') throw new RuntimeException('Worum geht es? Ein paar Stichworte genügen.');

        $s = ['type' => 'string'];
        $schema = [
            'type' => 'object',
            'properties' => [
                'ideas' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['kicker' => $s, 'heading' => $s, 'text' => $s, 'photo_search' => $s],
                    'required' => ['kicker', 'heading', 'text', 'photo_search'],
                    'additionalProperties' => false,
                ]],
            ],
            'required' => ['ideas'],
            'additionalProperties' => false,
        ];
        $kind = $template === 'offer' ? 'ein Angebot / eine Aktion' : 'einen Hinweis / eine Information';
        $prompt = "Schreibe für einen Bildschirm im Geschäft ({$org->industryLabel()}, Betrieb „{$org->name}“) drei unterschiedliche Vorschläge für $kind.\n"
            . "Stichworte des Betreibers: \"$idea\"\n\n"
            . "Regeln:\n- Deutsch (Österreich), freundlich, kurz – wird aus 3 Metern gelesen.\n"
            . "- kicker: 1–3 Wörter (z. B. „Nur heute“), heading: höchstens 6 Wörter, text: höchstens 2 kurze Sätze.\n"
            . "- Keine Preise, Rabatte oder Versprechen erfinden, die nicht in den Stichworten stehen.\n"
            . "- photo_search: 2–3 englische Suchbegriffe für ein passendes Foto.\n"
            . "- Vorschlag 1 sachlich, Vorschlag 2 herzlich, Vorschlag 3 mit Augenzwinkern.";

        $out = AiImport::request($org, [['type' => 'text', 'text' => $prompt]], $schema, 3000);
        return array_slice(array_values(array_filter($out['ideas'] ?? [], 'is_array')), 0, 3);
    }

    public static function searchPhotos(string $query, int $page = 1): array
    {
        if (! self::photosAvailable()) throw new RuntimeException('Die Bildbibliothek ist noch nicht eingerichtet.');
        $query = trim(mb_substr($query, 0, 100));
        if ($query === '') return [];
        $r = Http::withHeaders(['Authorization' => config('signo.pexels')])->timeout(12)
            ->get('https://api.pexels.com/v1/search', ['query' => $query, 'per_page' => 12, 'page' => $page, 'locale' => 'de-DE']);
        if (! $r->successful()) throw new RuntimeException('Die Bildsuche ist gerade nicht erreichbar.');
        return collect($r->json('photos', []))->map(fn ($p) => [
            'id' => $p['id'],
            'thumb' => $p['src']['medium'] ?? null,
            'full' => $p['src']['large2x'] ?? $p['src']['original'] ?? null,
            'by' => $p['photographer'] ?? '',
        ])->filter(fn ($p) => $p['thumb'] && $p['full'])->values()->all();
    }

    /** Foto herunterladen und im Speicher des Betriebs ablegen. Nur Pexels-Adressen. */
    public static function importPhoto(Organization $org, string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || $host !== 'images.pexels.com') {
            throw new RuntimeException('Ungültige Bildadresse.');
        }
        $r = Http::timeout(30)->get($url);
        $type = (string) $r->header('Content-Type');
        if (! $r->successful() || ! str_starts_with($type, 'image/') || strlen($r->body()) > 15_000_000) {
            throw new RuntimeException('Das Foto konnte nicht geladen werden.');
        }
        $ext = str_contains($type, 'png') ? 'png' : (str_contains($type, 'webp') ? 'webp' : 'jpg');
        $path = 'org/' . $org->id . '/' . Str::lower(Str::random(20)) . '.' . $ext;
        Storage::disk('public')->put($path, $r->body());
        return $path;
    }
}
