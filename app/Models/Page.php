<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = ['organization_id', 'title', 'template', 'theme', 'accent', 'font', 'data', 'slug', 'is_public', 'position'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_public' => 'boolean',
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

    /** Prüfsumme für den Player: ändert sich bei jeder Bearbeitung. */
    public function revision(): string
    {
        return substr(md5($this->id . '|' . $this->updated_at?->timestamp . '|' . json_encode($this->data) . $this->theme . $this->accent . $this->font), 0, 12);
    }
}
