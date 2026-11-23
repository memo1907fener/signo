<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Page;
use App\Services\AiImport;
use App\Support\PageData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/** Live-Demo auf der Startseite: ohne Anmeldung, nichts wird gespeichert. */
class DemoController extends Controller
{
    public function render(Request $request)
    {
        $v = $request->validate([
            'text' => 'nullable|string|max:3000',
            'theme' => 'nullable|in:light,dark',
            'accent' => 'nullable|string|max:20',
            'font' => 'nullable|string|max:20',
        ]);
        $data = self::parse((string) ($v['text'] ?? ''));
        return response()->json($this->output('list', $data, $v));
    }

    /** Foto einer Karte per KI auslesen – streng begrenzt. */
    public function photo(Request $request)
    {
        if (! AiImport::available()) return response()->json(['message' => 'Der Foto-Import ist in der Demo gerade nicht verfügbar.'], 503);
        $request->validate(['photo' => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,pdf']);

        $ipKey = 'demo-photo:' . $request->ip();
        $dayKey = 'demo-photo-day:' . now()->format('Ymd');
        if (RateLimiter::tooManyAttempts($ipKey, 3) || (int) Cache::get($dayKey, 0) >= 150) {
            return response()->json(['message' => 'Das Demo-Limit ist erreicht. Mit einem kostenlosen Konto geht es sofort weiter.'], 429);
        }
        RateLimiter::hit($ipKey, 86400);
        Cache::put($dayKey, (int) Cache::get($dayKey, 0) + 1, now()->endOfDay());

        try {
            $r = AiImport::run(new Organization(['name' => 'Demo', 'industry' => 'gastro']), $request->file('photo'), '', false);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => $e instanceof \RuntimeException ? $e->getMessage() : 'Das Foto konnte nicht gelesen werden.'], 422);
        }
        return response()->json($this->output($r['template'], $r['data'], $request->only('theme', 'accent', 'font')));
    }

    private function output(string $template, array $data, array $v): array
    {
        $page = new Page([
            'title' => 'Demo',
            'template' => $template,
            'theme' => ($v['theme'] ?? 'dark') === 'light' ? 'light' : 'dark',
            'accent' => array_key_exists($v['accent'] ?? '', config('signo.accents')) ? $v['accent'] : 'gold',
            'font' => array_key_exists($v['font'] ?? '', config('signo.fonts')) ? $v['font'] : 'elegant',
            'data' => PageData::clean($template, $data),
            'slug' => 'demo',
        ]);
        return [
            'tv' => view('display.page', ['page' => $page, 'mode' => 'tv'])->render(),
            'mobile' => view('display.page', ['page' => $page, 'mode' => 'mobile'])->render(),
        ];
    }

    /**
     * Freitext in eine Liste verwandeln:
     * erste Zeile ohne Preis = Überschrift, weitere Zeilen ohne Preis = Gruppen,
     * Zeilen mit Preis am Ende = Einträge ("Name – Beschreibung 12,90").
     */
    public static function parse(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn ($l) => $l !== ''));
        $heading = 'Speisekarte';
        $sections = [];
        $cur = null;
        foreach ($lines as $n => $line) {
            if (preg_match('/^(.*?)[\s.:…-]*((?:ab\s*)?€?\s*\d{1,4}(?:[.,]\d{1,2})?\s*€?)$/u', $line, $m) && trim($m[1]) !== '') {
                $name = trim($m[1], " \t-–—:");
                $desc = '';
                if (preg_match('/^(.*?)\s+[–—-]\s+(.*)$/u', $name, $mm)) { $name = $mm[1]; $desc = $mm[2]; }
                elseif (preg_match('/^(.*?)\s*\((.*)\)$/u', $name, $mm)) { $name = $mm[1]; $desc = $mm[2]; }
                $price = trim(str_replace('€', '', $m[2]));
                if ($cur === null) { $sections[] = ['title' => '', 'items' => []]; $cur = count($sections) - 1; }
                $sections[$cur]['items'][] = ['name' => $name, 'description' => $desc, 'price' => $price];
            } elseif ($n === 0) {
                $heading = $line;
            } else {
                $sections[] = ['title' => $line, 'items' => []];
                $cur = count($sections) - 1;
            }
        }
        if (! $sections) $sections = [['title' => '', 'items' => [['name' => 'Ihr erstes Gericht', 'description' => 'Einfach oben eintippen', 'price' => '9,90']]]];
        return ['heading' => mb_substr($heading, 0, 80), 'subheading' => '', 'sections' => array_slice($sections, 0, 8), 'footer' => ''];
    }
}
