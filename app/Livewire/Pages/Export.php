<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\WithOrg;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Export einer Seite: Bilder für Social Media, Tischaufsteller mit QR-Code, Druckvorlage. */
#[Layout('layouts.app')]
class Export extends Component
{
    use WithOrg;

    #[Locked]
    public int $pageId;
    public string $tentLine = '';

    public function mount(Page $page): void
    {
        abort_unless($page->organization_id === $this->org()->id, 404);
        abort_if(in_array($page->template, ['media', 'orders'], true), 404);
        $this->pageId = $page->id;
        $this->tentLine = __('Unsere Karte am Handy');
    }

    public function render()
    {
        $page = $this->org()->pages()->findOrFail($this->pageId);
        // Laufschrift gehört nicht aufs Bild
        $still = clone $page;
        $still->data = array_merge($page->data ?? [], ['ticker' => '']);

        return view('livewire.pages.export', ['page' => $page, 'still' => $still, 'org' => $this->org()])
            ->title(__('Export') . ' · ' . $page->title);
    }
}
