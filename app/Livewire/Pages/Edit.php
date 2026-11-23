<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\WithOrg;
use App\Models\Page;
use App\Services\AiImport;
use App\Services\Creative;
use App\Services\VoiceEdit;
use App\Support\PageData;
use App\Support\Translations;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Edit extends Component
{
    use WithOrg, WithFileUploads;

    #[Locked]
    public int $pageId;
    public string $title = '';
    public string $theme = 'light';
    public string $accent = 'coral';
    public string $font = 'modern';
    public bool $isPublic = true;
    public array $data = [];
    public $image = null;
    public ?string $savedAt = null;

    // Sprachen
    public array $languages = [];
    public bool $tvLanguages = false;
    public ?string $langMessage = null;
    public bool $langError = false;

    public string $previewLang = 'de';

    // Gestaltungshilfe
    public string $idea = '';
    public array $ideas = [];
    public ?string $ideaError = null;
    public bool $photoOpen = false;
    public string $photoQuery = '';
    public array $photos = [];
    public ?string $photoError = null;

    // Zeitplan
    public array $schedule = ['enabled' => false, 'from' => '', 'until' => '', 'days' => [1, 2, 3, 4, 5, 6, 7], 'start' => '', 'end' => ''];

    // Bestellen
    public bool $orderable = false;

    // Ändern per Sprache
    public string $voiceText = '';
    public ?array $voicePreview = null;
    public ?string $voiceError = null;

    public function mount(Page $page): void
    {
        abort_unless($page->organization_id === $this->org()->id, 404);
        $this->pageId = $page->id;
        $this->title = $page->title;
        $this->theme = $page->theme;
        $this->accent = $page->accent;
        $this->font = $page->font;
        $this->isPublic = $page->is_public;
        $this->data = PageData::clean($page->template, $page->data);
        $this->languages = $page->languages ?? [];
        $this->tvLanguages = $page->tv_languages;
        $this->orderable = $page->orderable;
        $this->schedule = array_merge($this->schedule, $page->schedule ?? []);
    }

    protected function page(): Page
    {
        return $this->org()->pages()->findOrFail($this->pageId);
    }

    /** Jede Änderung wird sofort gespeichert. */
    public function updated($name): void
    {
        if (in_array($name, ['image', 'voiceText', 'previewLang', 'idea', 'photoQuery', 'photoOpen'], true)) return;
        $this->persist();
    }

    protected function persist(): void
    {
        $page = $this->page();
        $this->title = mb_substr(trim($this->title), 0, 80) ?: 'Ohne Titel';
        if (! in_array($this->theme, ['light', 'dark'], true)) $this->theme = 'light';
        if (! array_key_exists($this->accent, config('signo.accents')) && ! ($this->accent === 'brand' && $this->org()->brandAccent())) $this->accent = 'coral';
        if (! array_key_exists($this->font, config('signo.fonts'))) $this->font = 'modern';
        $page->update([
            'title' => $this->title,
            'theme' => $this->theme,
            'accent' => $this->accent,
            'font' => $this->font,
            'is_public' => $this->isPublic,
            'tv_languages' => $this->tvLanguages,
            'schedule' => $this->cleanSchedule(),
            'orderable' => $this->orderable && $page->template === 'list',
            'data' => PageData::clean($page->template, $this->data),
        ]);
        $this->savedAt = now()->format('H:i:s');
    }

    protected function cleanSchedule(): ?array
    {
        $s = $this->schedule;
        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
        $time = fn ($v) => is_string($v) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : null;
        $days = array_values(array_unique(array_filter(array_map('intval', (array) ($s['days'] ?? [])), fn ($d) => $d >= 1 && $d <= 7)));
        sort($days);
        return [
            'enabled' => (bool) ($s['enabled'] ?? false),
            'from' => $date($s['from'] ?? null),
            'until' => $date($s['until'] ?? null),
            'days' => $days ?: [1, 2, 3, 4, 5, 6, 7],
            'start' => $time($s['start'] ?? null),
            'end' => $time($s['end'] ?? null),
        ];
    }

    // ---------- Gestaltungshilfe ----------
    public function suggest(): void
    {
        $this->ideaError = null; $this->ideas = [];
        try {
            $this->ideas = Creative::ideas($this->org(), $this->page()->template, $this->idea);
        } catch (\Throwable $e) {
            report($e);
            $this->ideaError = $e instanceof \RuntimeException ? $e->getMessage() : __('Das hat nicht geklappt.');
        }
    }

    public function useIdea(int $i): void
    {
        $idea = $this->ideas[$i] ?? null;
        if (! $idea) return;
        foreach (['kicker', 'heading', 'text'] as $k) {
            if (array_key_exists($k, $this->data) && isset($idea[$k])) $this->data[$k] = $idea[$k];
        }
        $this->photoQuery = (string) ($idea['photo_search'] ?? '');
        $this->ideas = [];
        $this->persist();
    }

    public function openPhotos(): void
    {
        $this->photoOpen = true;
        if ($this->photoQuery === '') $this->photoQuery = (string) ($this->data['heading'] ?? '');
        $this->searchPhotos();
    }

    public function searchPhotos(): void
    {
        $this->photoError = null;
        try {
            $this->photos = Creative::searchPhotos($this->photoQuery);
            if (! $this->photos) $this->photoError = __('Keine Fotos gefunden. Andere Suchbegriffe probieren (Englisch funktioniert oft besser).');
        } catch (\Throwable $e) {
            report($e);
            $this->photoError = $e instanceof \RuntimeException ? $e->getMessage() : __('Die Bildsuche ist gerade nicht erreichbar.');
        }
    }

    public function pickPhoto(int $i): void
    {
        $p = $this->photos[$i] ?? null;
        if (! $p) return;
        try {
            $path = Creative::importPhoto($this->org(), $p['full']);
        } catch (\Throwable $e) {
            report($e);
            $this->photoError = $e instanceof \RuntimeException ? $e->getMessage() : __('Das Foto konnte nicht geladen werden.');
            return;
        }
        $key = $this->page()->template === 'media' ? 'file' : 'image';
        $this->deleteFile($this->data[$key] ?? null);
        $this->data[$key] = $path;
        if ($key === 'file') $this->data['mime'] = 'image/jpeg';
        $this->photoOpen = false;
        $this->photos = [];
        $this->persist();
    }

    // ---------- Sprachen ----------
    public function toggleLanguage(string $code): void
    {
        if (! array_key_exists($code, Translations::LANGUAGES)) return;
        $this->languages = in_array($code, $this->languages, true)
            ? array_values(array_diff($this->languages, [$code]))
            : array_merge($this->languages, [$code]);
        if (count($this->languages) > 6) {
            array_pop($this->languages);
            $this->langError = true;
            $this->langMessage = __('Höchstens 6 Sprachen je Seite.');
            return;
        }
        $this->page()->update(['languages' => $this->languages]);
        $this->translate();
    }

    public function translate(): void
    {
        $this->langMessage = null; $this->langError = false;
        $page = $this->page();
        try {
            Translations::translate($this->org(), $page);
            $this->langMessage = $this->languages ? __('Übersetzung aktuell.') : null;
        } catch (\Throwable $e) {
            report($e);
            $this->langError = true;
            $this->langMessage = $e instanceof \RuntimeException ? $e->getMessage() : __('Übersetzung fehlgeschlagen.');
        }
    }

    // ---------- Ändern per Sprache ----------
    public function voiceRun(): void
    {
        $this->voiceError = null; $this->voicePreview = null;
        try {
            $r = VoiceEdit::run($this->page(), $this->voiceText);
            $this->voicePreview = $r;
        } catch (\Throwable $e) {
            report($e);
            $this->voiceError = $e instanceof \RuntimeException ? $e->getMessage() : __('Das hat nicht geklappt. Bitte erneut versuchen.');
        }
    }

    public function voiceApply(): void
    {
        if (! $this->voicePreview) return;
        $this->data = PageData::clean($this->page()->template, $this->voicePreview['data']);
        $this->voicePreview = null;
        $this->voiceText = '';
        $this->persist();
        if ($this->languages) $this->translate();
    }

    public function voiceDiscard(): void
    {
        $this->voicePreview = null;
    }

    // ---------- Kennzeichen, Ausverkauft, Wochenmenü ----------
    private function itemPath(string $path): string
    {
        abort_unless(preg_match('/^(sections|days)\.\d{1,2}\.items\.\d{1,2}$/', $path) === 1, 400);
        return $path;
    }

    public function toggleTag(string $path, string $tag): void
    {
        $path = $this->itemPath($path);
        if (data_get($this->data, $path) === null) return;
        $tags = (array) data_get($this->data, "$path.tags", []);
        $tags = in_array($tag, $tags, true) ? array_diff($tags, [$tag]) : array_merge($tags, [$tag]);
        data_set($this->data, "$path.tags", PageData::tags($tags));
        $this->persist();
    }

    /** "Heute aus": gilt bis Tagesende und verschwindet danach von selbst. */
    public function toggleSoldOut(string $path): void
    {
        $path = $this->itemPath($path);
        $item = data_get($this->data, $path);
        if (! is_array($item)) return;
        data_set($this->data, "$path.soldout", Page::soldOut($item) ? '' : now()->timezone(config('app.timezone'))->format('Y-m-d'));
        $this->persist();
    }

    public function addDayItem(int $day): void
    {
        if ($day < 0 || $day > 6) return;
        $this->data['days'][$day]['items'][] = ['name' => '', 'description' => '', 'price' => '', 'tags' => [], 'soldout' => '', 'happy' => ''];
        $this->persist();
    }

    public function removeDayItem(int $day, int $i): void
    {
        unset($this->data['days'][$day]['items'][$i]);
        $this->data['days'][$day]['items'] = array_values($this->data['days'][$day]['items'] ?? []);
        $this->persist();
    }

    /** Einen Tag auf einen anderen kopieren (z. B. Montag → Dienstag). */
    public function copyDay(int $from, int $to): void
    {
        if ($from < 0 || $from > 6 || $to < 0 || $to > 6 || $from === $to) return;
        $this->data['days'][$to]['items'] = $this->data['days'][$from]['items'] ?? [];
        $this->persist();
    }

    // ---------- Listen ----------
    public function addSection(): void
    {
        $this->data['sections'][] = ['title' => '', 'items' => [['name' => '', 'description' => '', 'price' => '']]];
        $this->persist();
    }

    public function removeSection(int $s): void
    {
        unset($this->data['sections'][$s]);
        $this->data['sections'] = array_values($this->data['sections']);
        $this->persist();
    }

    public function addItem(int $s): void
    {
        $this->data['sections'][$s]['items'][] = ['name' => '', 'description' => '', 'price' => ''];
        $this->persist();
    }

    public function removeItem(int $s, int $i): void
    {
        unset($this->data['sections'][$s]['items'][$i]);
        $this->data['sections'][$s]['items'] = array_values($this->data['sections'][$s]['items']);
        $this->persist();
    }

    public function moveItem(int $s, int $i, int $dir): void
    {
        $items = $this->data['sections'][$s]['items'] ?? [];
        $j = $i + $dir;
        if (! isset($items[$i], $items[$j])) return;
        [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        $this->data['sections'][$s]['items'] = $items;
        $this->persist();
    }

    public function moveSection(int $s, int $dir): void
    {
        $sec = $this->data['sections'] ?? [];
        $j = $s + $dir;
        if (! isset($sec[$s], $sec[$j])) return;
        [$sec[$s], $sec[$j]] = [$sec[$j], $sec[$s]];
        $this->data['sections'] = $sec;
        $this->persist();
    }

    public function addRow(): void
    {
        $this->data['rows'][] = ['label' => '', 'value' => ''];
        $this->persist();
    }

    public function removeRow(int $r): void
    {
        unset($this->data['rows'][$r]);
        $this->data['rows'] = array_values($this->data['rows']);
        $this->persist();
    }

    // ---------- Bilder / Videos ----------
    public function updatedImage(): void
    {
        $page = $this->page();
        $isMedia = $page->template === 'media';
        $this->validate([
            'image' => $isMedia
                ? 'file|max:102400|mimes:jpg,jpeg,png,webp,gif,mp4,webm'
                : 'file|max:15360|mimes:jpg,jpeg,png,webp',
        ], [
            'image.mimes' => $isMedia ? __('Erlaubt: JPG, PNG, WEBP, GIF, MP4, WEBM.') : __('Erlaubt: JPG, PNG, WEBP.'),
            'image.max' => $isMedia ? __('Maximal 100 MB.') : __('Maximal 15 MB.'),
        ]);

        $ext = strtolower($this->image->getClientOriginalExtension() ?: $this->image->extension());
        $name = Str::lower(Str::random(20)) . '.' . $ext;
        $dir = 'org/' . $page->organization_id;
        $path = $this->image->storeAs($dir, $name, 'public');
        $key = $isMedia ? 'file' : 'image';

        $this->deleteFile($this->data[$key] ?? null);
        $this->data[$key] = $path;
        if ($isMedia) $this->data['mime'] = $this->image->getMimeType();
        $this->image = null;
        $this->persist();
    }

    public function removeImage(): void
    {
        $key = $this->page()->template === 'media' ? 'file' : 'image';
        $this->deleteFile($this->data[$key] ?? null);
        $this->data[$key] = null;
        $this->persist();
    }

    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'org/' . $this->org()->id . '/')) {
            // Nur löschen, wenn keine andere Seite die Datei nutzt (z. B. nach Duplizieren)
            $used = Page::where('organization_id', $this->org()->id)->where('id', '!=', $this->pageId)
                ->where('data', 'like', '%' . $path . '%')->exists();
            if (! $used) Storage::disk('public')->delete($path);
        }
    }

    public function render()
    {
        $page = $this->page();
        // Vorschau aus dem aktuellen (ungespeicherten) Zustand
        $preview = $page->replicate();
        $preview->id = $page->id;
        $preview->fill([
            'theme' => $this->theme, 'accent' => $this->accent, 'font' => $this->font,
            'data' => PageData::clean($page->template, $this->data),
        ]);

        $org = $this->org();
        return view('livewire.pages.edit', [
            'page' => $page, 'preview' => $preview, 'org' => $org,
            'aiReady' => AiImport::available(),
            'photosReady' => Creative::photosAvailable(),
            'missing' => $page->languages ? Translations::missingCount($page) : 0,
        ])
            ->title($this->title);
    }
}
