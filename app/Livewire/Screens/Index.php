<?php

namespace App\Livewire\Screens;

use App\Livewire\Concerns\WithOrg;
use App\Models\Screen;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bildschirme')]
class Index extends Component
{
    use WithOrg;

    public bool $adding = false;
    public string $code = '';
    public string $name = '';

    // Eilmeldung
    public string $noticeText = '';
    public string $noticeFor = 'today';   // 60 | today | open
    public bool $noticeFull = false;

    public function sendNotice(): void
    {
        $text = mb_substr(trim($this->noticeText), 0, 160);
        if ($text === '') { $this->addError('noticeText', __('Bitte einen Text eingeben.')); return; }
        $until = match ($this->noticeFor) {
            '60' => now()->addHour(),
            'open' => null,
            default => now()->timezone(config('app.timezone'))->endOfDay(),
        };
        $this->org()->update(['notice' => ['text' => $text, 'full' => $this->noticeFull, 'until' => $until?->toIso8601String()]]);
        $this->reset('noticeText');
    }

    public function toggleAlerts(): void
    {
        $org = $this->org();
        $org->update(['offline_alerts' => ! $org->offline_alerts]);
    }

    public function clearNotice(): void
    {
        $this->org()->update(['notice' => null]);
    }

    public function pair()
    {
        $org = $this->org();
        $key = 'pair:' . $org->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('code', __('Zu viele Versuche. Bitte kurz warten.'));
            return;
        }
        RateLimiter::hit($key, 600);

        $this->code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->code));
        $this->validate(['code' => 'required|size:6', 'name' => 'nullable|string|max:60'], [
            'code.required' => __('Bitte den Code vom Bildschirm eingeben.'),
            'code.size' => __('Der Code hat 6 Zeichen.'),
        ]);

        if ($org->screens()->count() >= $org->screenLimit()) {
            $this->addError('code', $org->screenLimit() === 0
                ? __('Ihr Paket enthält keine Bildschirme. Wählen Sie unter „Konto“ ein Paket mit Bildschirm.')
                : 'Ihr Paket erlaubt ' . $org->screenLimit() . ' Bildschirm(e). Für mehr bitte unter „Konto“ das Paket wechseln.');
            return;
        }

        $screen = Screen::whereNull('organization_id')->where('pair_code', $this->code)->first();
        if (! $screen) {
            $this->addError('code', __('Code nicht gefunden. Steht er noch auf dem Bildschirm?'));
            return;
        }

        $screen->update([
            'organization_id' => $org->id,
            'name' => trim($this->name) ?: 'Bildschirm ' . ($org->screens()->count() + 1),
            'pair_code' => null,
            'paired_at' => now(),
            'show_all' => true,
        ]);

        $this->reset('adding', 'code', 'name');
        session()->flash('ok', __('Verbunden! Der Bildschirm zeigt Ihre Inhalte in wenigen Sekunden.'));
        return $this->redirectRoute('screens.edit', $screen, navigate: true);
    }

    public function render()
    {
        $org = $this->org();
        return view('livewire.screens.index', [
            'org' => $org,
            'screens' => $org->screens()->get(),
        ]);
    }
}
