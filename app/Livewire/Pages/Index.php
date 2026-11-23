<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\WithOrg;
use App\Models\Page;
use App\Services\AiImport;
use App\Support\Starter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Inhalte')]
class Index extends Component
{
    use WithOrg, WithFileUploads;

    public bool $creating = false;
    public string $mode = 'ai';      // ai | template
    public $upload = null;
    public string $text = '';
    public ?string $aiError = null;
    public ?int $qrPage = null;

    public function create(string $template)
    {
        abort_unless(array_key_exists($template, config('signo.templates')) && ! (config("signo.templates.$template.hidden") ?? false), 404);
        $org = $this->org();
        $page = Page::create([
            'organization_id' => $org->id,
            'title' => config("signo.templates.$template.label"),
            'template' => $template,
            'theme' => 'light',
            'accent' => $org->brandAccent() ? 'brand' : 'coral',
            'data' => Starter::blank($template),
            'position' => (int) $org->pages()->max('position') + 1,
        ]);
        return $this->redirectRoute('pages.edit', $page, navigate: true);
    }

    public function import()
    {
        $this->aiError = null;
        $this->validate([
            'upload' => 'nullable|file|max:15360|mimes:jpg,jpeg,png,webp,pdf',
            'text' => 'nullable|string|max:20000',
        ], [
            'upload.mimes' => __('Bitte ein Foto (JPG, PNG) oder ein PDF hochladen.'),
            'upload.max' => __('Die Datei ist größer als 15 MB.'),
        ]);
        if (! $this->upload && trim($this->text) === '') {
            $this->aiError = __('Bitte ein Foto oder PDF hochladen oder Text einfügen.');
            return;
        }

        $org = $this->org();
        try {
            $r = AiImport::run($org, $this->upload, $this->text);
        } catch (\Throwable $e) {
            $this->aiError = $e instanceof \RuntimeException ? $e->getMessage() : __('Unerwarteter Fehler beim KI-Import.');
            report($e);
            return;
        }

        $page = Page::create([
            'organization_id' => $org->id,
            'title' => $r['title'],
            'template' => $r['template'],
            'theme' => $org->industry === 'gastro' ? 'dark' : 'light',
            'accent' => $org->brandAccent() ? 'brand' : ($org->industry === 'gastro' ? 'gold' : 'coral'),
            'font' => $org->industry === 'gastro' ? 'elegant' : 'modern',
            'data' => $r['data'],
            'position' => (int) $org->pages()->max('position') + 1,
        ]);
        session()->flash('ok', __('Fertig! Bitte kurz prüfen, ob alles stimmt.'));
        return $this->redirectRoute('pages.edit', $page, navigate: true);
    }

    public function duplicate(int $id): void
    {
        $page = $this->org()->pages()->findOrFail($id);
        $copy = $page->replicate(['slug']);
        $copy->title = $page->title . ' (Kopie)';
        $copy->position = $page->position + 1;
        $copy->save();
    }

    public function delete(int $id): void
    {
        $this->org()->pages()->findOrFail($id)->delete();
    }

    public function move(int $id, int $dir): void
    {
        $pages = $this->org()->pages()->get()->values();
        $i = $pages->search(fn ($p) => $p->id === $id);
        $j = $i + $dir;
        if ($i === false || $j < 0 || $j >= $pages->count()) return;
        $list = $pages->all();
        [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        foreach ($list as $pos => $p) $p->update(['position' => $pos]);
    }

    public function render()
    {
        $org = $this->org();
        return view('livewire.pages.index', [
            'org' => $org,
            'pages' => $org->pages()->withCount('screens')->get(),
            'aiReady' => AiImport::available(),
            'qr' => $this->qrPage ? $org->pages()->find($this->qrPage) : null,
        ]);
    }
}
