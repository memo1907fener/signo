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
        return config("signo.accents.{$this->accent}", '#ff5a36');
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
        return substr(md5($this->id . '|' . $this->updated_at?->timestamp . '|' . json_encode($this->data) . json_encode($this->translations) . (int) $this->tv_languages . $this->theme . $this->accent . $this->font), 0, 12);
    }
}
