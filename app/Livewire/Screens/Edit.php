<?php

namespace App\Livewire\Screens;

use App\Livewire\Concerns\WithOrg;
use App\Models\Screen;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    use WithOrg;

    #[Locked]
    public int $screenId;
    public string $name = '';
    public bool $showAll = true;
    public array $selected = [];     // Seiten-IDs in Reihenfolge
    public int $seconds = 12;
    public bool $alwaysOn = true;
    public string $onTime = '08:00';
    public string $offTime = '20:00';
    public array $days = [1, 2, 3, 4, 5, 6, 7];
    public ?string $flash = null;

    public function mount(Screen $screen): void
    {
        abort_unless($screen->organization_id === $this->org()->id, 404);
        $this->screenId = $screen->id;
        $this->name = (string) $screen->name;
        $this->showAll = $screen->show_all;
        $this->selected = $screen->pages()->pluck('pages.id')->all();
        $this->seconds = $screen->seconds;
        $this->alwaysOn = $screen->always_on;
        $this->onTime = $screen->on_time ?: '08:00';
        $this->offTime = $screen->off_time ?: '20:00';
        $this->days = $screen->days ?: [1, 2, 3, 4, 5, 6, 7];
    }

    protected function screen(): Screen
    {
        return $this->org()->screens()->findOrFail($this->screenId);
    }

    public function updated(): void
    {
        $this->save();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:60',
            'seconds' => 'required|integer|min:4|max:600',
            'onTime' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'offTime' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ], [
            'name.required' => __('Bitte einen Namen eingeben.'),
            'seconds.min' => __('Mindestens 4 Sekunden.'),
        ]);

        $screen = $this->screen();
        $days = array_values(array_unique(array_map('intval', array_filter($this->days, fn ($d) => $d >= 1 && $d <= 7))));
        sort($days);
        $screen->update([
            'name' => $this->name,
            'show_all' => $this->showAll,
            'seconds' => $this->seconds,
            'always_on' => $this->alwaysOn || $this->onTime === $this->offTime,
            'on_time' => $this->onTime,
            'off_time' => $this->offTime,
            'days' => $days ?: [1, 2, 3, 4, 5, 6, 7],
        ]);

        $valid = $this->org()->pages()->pluck('id')->all();
        $ids = array_values(array_intersect(array_map('intval', $this->selected), $valid));
        $sync = [];
        foreach ($ids as $pos => $id) $sync[$id] = ['position' => $pos];
        $screen->pages()->sync($sync);
        $this->selected = $ids;
    }

    public function togglePage(int $id): void
    {
        if (in_array($id, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$id]));
        } else {
            $this->selected[] = $id;
        }
        $this->save();
    }

    public function moveSelected(int $id, int $dir): void
    {
        $i = array_search($id, $this->selected);
        $j = $i + $dir;
        if ($i === false || ! isset($this->selected[$j])) return;
        [$this->selected[$i], $this->selected[$j]] = [$this->selected[$j], $this->selected[$i]];
        $this->save();
    }

    public function command(string $cmd): void
    {
        abort_unless(in_array($cmd, ['reload', 'restart_app', 'screenshot'], true), 400);
        $screen = $this->screen();
        $screen->commands()->whereNull('acked_at')->where('command', $cmd)->delete();
        $screen->commands()->create(['command' => $cmd, 'expires_at' => now()->addMinutes(10)]);
        $this->flash = match ($cmd) {
            'reload' => __('Der Bildschirm lädt in den nächsten Sekunden neu.'),
            'restart_app' => __('Die App wird neu gestartet (nur Android-App).'),
            'screenshot' => __('Bildschirmfoto angefordert – erscheint in ca. 30 Sekunden.'),
        };
    }

    public function unpair()
    {
        $screen = $this->screen();
        $screen->pages()->detach();
        $screen->commands()->delete();
        $screen->delete();
        session()->flash('ok', __('Bildschirm entfernt. Am Gerät erscheint wieder ein neuer Code.'));
        return $this->redirectRoute('screens.index', navigate: true);
    }

    public function render()
    {
        $screen = $this->screen();
        return view('livewire.screens.edit', [
            'screen' => $screen,
            'pages' => $this->org()->pages()->get(),
        ])->title($screen->name ?? 'Bildschirm');
    }
}
