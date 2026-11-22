<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\WithOrg;
use App\Models\Page;
use App\Support\PageData;
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
    }

    protected function page(): Page
    {
        return $this->org()->pages()->findOrFail($this->pageId);
    }

    /** Jede Änderung wird sofort gespeichert. */
    public function updated($name): void
    {
        if ($name === 'image') return;
        $this->persist();
    }

    protected function persist(): void
    {
        $page = $this->page();
        $this->title = mb_substr(trim($this->title), 0, 80) ?: 'Ohne Titel';
        if (! in_array($this->theme, ['light', 'dark'], true)) $this->theme = 'light';
        if (! array_key_exists($this->accent, config('signo.accents'))) $this->accent = 'coral';
        if (! array_key_exists($this->font, config('signo.fonts'))) $this->font = 'modern';
        $page->update([
            'title' => $this->title,
            'theme' => $this->theme,
            'accent' => $this->accent,
            'font' => $this->font,
            'is_public' => $this->isPublic,
            'data' => PageData::clean($page->template, $this->data),
        ]);
        $this->savedAt = now()->format('H:i:s');
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
            'image.mimes' => $isMedia ? 'Erlaubt: JPG, PNG, WEBP, GIF, MP4, WEBM.' : 'Erlaubt: JPG, PNG, WEBP.',
            'image.max' => $isMedia ? 'Maximal 100 MB.' : 'Maximal 15 MB.',
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

        return view('livewire.pages.edit', ['page' => $page, 'preview' => $preview])
            ->title($this->title);
    }
}
