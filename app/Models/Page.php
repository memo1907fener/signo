<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'organization_id', 'title', 'template', 'theme', 'accent', 'font', 'data', 'slug', 'is_public', 'position',
        'languages', 'translations', 'translated_hash', 'tv_languages', 'orderable', 'schedule',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_public' => 'boolean',
            'languages' => 'array',
            'translations' => 'array',
            'tv_languages' => 'boolean',
            'orderable' => 'boolean',
            'schedule' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Page $page) {
            if (! $page->slug) {
                do { $slug = Str::lower(Str::random(10)); } while (static::where('slug', $slug)->exists());
                $page->slug = $slug;
            }
        });
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function screens(): BelongsToMany { return $this->belongsToMany(Screen::class); }

    public function accentColor(): string
    {
        if ($this->accent === 'brand') return $this->organization?->brandAccent() ?? '#ff5a36';
        return config("signo.accents.{$this->accent}", '#ff5a36');
    }

    /** Hintergrund- und Schriftfarbe je Design und Helligkeit (eine Quelle für Bildschirm und Handy-Seite). */
    public function colors(): array
    {
        $dark = $this->theme === 'dark';
        return match ($this->font) {
            'bistro' => $dark ? ['#1c1512', '#f4e9d8'] : ['#f6efe3', '#2a1d14'],
            'chalk' => $dark ? ['#1f2a26', '#f3f1e7'] : ['#fbf7ee', '#3a3a38'],
            'minimal' => $dark ? ['#0c0c0d', '#f4f4f2'] : ['#ffffff', '#141414'],
            'neon' => $dark ? ['#0b0714', '#f4f0ff'] : ['#ffffff', '#14101f'],
            default => $dark ? ['#111214', '#f5f2ec'] : ['#f7f4ef', '#17140f'],
        };
    }

    /** Gilt gerade die Happy Hour dieser Liste? */
    public function happyActive(?\Illuminate\Support\Carbon $at = null): bool
    {
        $h = $this->data['happy'] ?? null;
        if ($this->template !== 'list' || ! $h || empty($h['enabled']) || empty($h['start']) || empty($h['end'])) return false;
        $at = ($at ?? now())->copy()->timezone(config('app.timezone'));
        $hm = $at->format('H:i');
        $days = array_map('intval', $h['days'] ?? []) ?: [1, 2, 3, 4, 5, 6, 7];
        if ($h['start'] < $h['end']) return in_array($at->dayOfWeekIso, $days, true) && $hm >= $h['start'] && $hm < $h['end'];
        // über Mitternacht: der Tag zählt, an dem die Happy Hour begonnen hat
        if ($hm >= $h['start']) return in_array($at->dayOfWeekIso, $days, true);
        if ($hm < $h['end']) return in_array($at->copy()->subDay()->dayOfWeekIso, $days, true);
        return false;
    }

    /** Ist der Eintrag heute als ausverkauft markiert? */
    public static function soldOut(array $item): bool
    {
        return ($item['soldout'] ?? '') === now()->timezone(config('app.timezone'))->format('Y-m-d');
    }

    /** Aktuell gültiger Preis eines Eintrags: [Preis, durchgestrichener Normalpreis oder null]. */
    public function priceOf(array $item): array
    {
        $happy = trim((string) ($item['happy'] ?? ''));
        if ($happy !== '' && $this->happyActive()) return [$happy, $item['price'] ?? ''];
        return [$item['price'] ?? '', null];
    }

    /** Alle auf der Seite verwendeten Kennzeichen (für die Legende). */
    public function usedTags(): array
    {
        $d = $this->data ?? [];
        $groups = $this->template === 'week' ? ($d['days'] ?? []) : ($d['sections'] ?? []);
        $used = [];
        foreach ($groups as $g) foreach (($g['items'] ?? []) as $it) foreach (($it['tags'] ?? []) as $t) $used[$t] = true;
        return \App\Support\PageData::tags(array_keys($used));
    }

    public function templateLabel(): string
    {
        return config("signo.templates.{$this->template}.label", $this->template);
    }

    public function publicUrl(): string
    {
        return url('/m/' . $this->slug);
    }

    /** Öffentliche Adresse einer hochgeladenen Datei. */
    public static function fileUrl(?string $path): ?string
    {
        if (! $path) return null;
        if (Str::startsWith($path, ['http://', 'https://', '/'])) return $path;
        return '/storage/' . ltrim($path, '/');
    }

    /** Läuft die Seite laut Zeitplan gerade? */
    public function isActiveAt(?\Illuminate\Support\Carbon $at = null): bool
    {
        $s = $this->schedule;
        if (! $s || empty($s['enabled'])) return true;
        $at = ($at ?? now())->copy()->timezone(config('app.timezone'));
        $date = $at->format('Y-m-d');
        if (! empty($s['from']) && $date < $s['from']) return false;
        if (! empty($s['until']) && $date > $s['until']) return false;
        $days = $s['days'] ?? [];
        if ($days && ! in_array($at->dayOfWeekIso, array_map('intval', $days), true)) return false;
        if (! empty($s['start']) && ! empty($s['end'])) {
            $hm = $at->format('H:i');
            if ($s['start'] < $s['end']) return $hm >= $s['start'] && $hm < $s['end'];
            return $hm >= $s['start'] || $hm < $s['end'];   // über Mitternacht
        }
        return true;
    }

    public function scheduleLabel(): ?string
    {
        $s = $this->schedule;
        if (! $s || empty($s['enabled'])) return null;
        $parts = [];
        $names = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];
        $days = array_map('intval', $s['days'] ?? []);
        if ($days && count($days) < 7) {
            sort($days);
            $parts[] = $days === [1, 2, 3, 4, 5] ? 'Mo–Fr' : ($days === [6, 7] ? 'Sa–So' : implode(', ', array_map(fn ($d) => $names[$d], $days)));
        }
        if (! empty($s['start']) && ! empty($s['end'])) $parts[] = $s['start'] . '–' . $s['end'];
        if (! empty($s['from']) || ! empty($s['until'])) {
            $f = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d.m.') : '…';
            $parts[] = $f($s['from'] ?? null) . ' bis ' . $f($s['until'] ?? null);
        }
        return $parts ? implode(' · ', $parts) : 'Zeitplan';
    }

    /** Diese Seite in einer anderen Sprache (Übersetzung über die Originaldaten gelegt). */
    public function inLanguage(?string $lang): Page
    {
        if (! $lang || $lang === 'de' || empty($this->translations[$lang])) return $this;
        $copy = clone $this;
        $copy->data = \App\Support\Translations::apply($this->template, $this->data ?? [], $this->translations[$lang]);
        $copy->setAttribute('lang', $lang);
        return $copy;
    }

    /** Sprachen mit fertiger Übersetzung. */
    public function availableLanguages(): array
    {
        return array_values(array_filter($this->languages ?? [], fn ($l) => ! empty($this->translations[$l])));
    }

    public function translationsOutdated(): bool
    {
        return ! empty($this->languages) && \App\Support\Translations::missingCount($this) > 0;
    }

    /** Prüfsumme für den Player: ändert sich bei jeder Bearbeitung. */
    public function revision(): string
    {
        // Datum, Happy Hour und Marke fließen ein: Wochenmenü, "ausverkauft" und Aktionspreise wechseln von selbst
        $dynamic = now()->timezone(config('app.timezone'))->format('Ymd') . (int) $this->happyActive() . md5(json_encode($this->organization?->brand));
        return substr(md5($this->id . '|' . $this->updated_at?->timestamp . '|' . json_encode($this->data) . json_encode($this->translations) . (int) $this->tv_languages . $this->theme . $this->accent . $this->font . $dynamic), 0, 12);
    }
}
