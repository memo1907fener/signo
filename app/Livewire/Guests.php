<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithOrg;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Gäste: Feedback per QR-Code und digitale Stempelkarte. */
#[Layout('layouts.app')]
class Guests extends Component
{
    use WithOrg;

    public bool $feedbackOn = false;
    public string $googleUrl = '';
    public bool $loyaltyOn = false;
    public string $cardTitle = '';
    public string $cardReward = '';
    public int $cardGoal = 10;
    public string $cardPin = '';
    public ?string $saved = null;

    public function mount(): void
    {
        $org = $this->org();
        $this->feedbackOn = $org->feedback_enabled;
        $this->googleUrl = (string) $org->google_review_url;
        $l = $org->loyalty ?? [];
        $this->loyaltyOn = (bool) ($l['enabled'] ?? false);
        $this->cardTitle = (string) ($l['title'] ?? '');
        $this->cardReward = (string) ($l['reward'] ?? '');
        $this->cardGoal = (int) ($l['goal'] ?? 10);
        $this->cardPin = (string) ($l['pin'] ?? '');
    }

    public function updatedFeedbackOn(): void { $this->saveFeedback(); }

    public function saveFeedback(): void
    {
        $this->googleUrl = trim($this->googleUrl);
        $this->validate(['googleUrl' => ['nullable', 'url:https', 'max:500']], ['googleUrl.url' => __('Bitte den vollständigen Link eingeben, beginnend mit https://')]);
        $org = $this->org();
        $org->guestToken();
        $org->update(['feedback_enabled' => $this->feedbackOn, 'google_review_url' => $this->googleUrl ?: null]);
        $this->saved = 'feedback';
    }

    public function updatedLoyaltyOn(): void
    {
        // Beim ersten Einschalten sinnvolle Startwerte, damit die Karte sofort funktioniert
        if ($this->loyaltyOn) {
            if ($this->cardPin === '') $this->cardPin = (string) random_int(1000, 9999);
            if ($this->cardTitle === '') $this->cardTitle = __('Treuekarte');
            if ($this->cardReward === '') $this->cardReward = __('Das 10. Getränk ist gratis');
        }
        $this->saveLoyalty();
    }

    public function saveLoyalty(): void
    {
        $this->validate([
            'cardTitle' => 'nullable|string|max:60',
            'cardReward' => 'nullable|string|max:120',
            'cardGoal' => 'required|integer|min:3|max:20',
            'cardPin' => [$this->loyaltyOn ? 'required' : 'nullable', 'digits_between:4,6'],
        ], [
            'cardPin.required' => __('Bitte einen Code mit 4 bis 6 Ziffern festlegen.'),
            'cardPin.digits_between' => __('Bitte einen Code mit 4 bis 6 Ziffern festlegen.'),
        ]);
        $org = $this->org();
        $org->guestToken();
        $org->update(['loyalty' => [
            'enabled' => $this->loyaltyOn,
            'title' => trim($this->cardTitle),
            'reward' => trim($this->cardReward),
            'goal' => $this->cardGoal,
            'pin' => $this->cardPin,
        ]]);
        $this->saved = 'loyalty';
    }

    public function newPin(): void
    {
        $this->cardPin = (string) random_int(1000, 9999);
        $this->saveLoyalty();
    }

    public function deleteFeedback(int $id): void
    {
        $this->org()->feedbacks()->whereKey($id)->delete();
    }

    public function render()
    {
        $org = $this->org();
        $all = $org->feedbacks();
        $count = (clone $all)->count();
        $dist = (clone $all)->selectRaw('rating, count(*) as n')->groupBy('rating')->pluck('n', 'rating');
        $list = (clone $all)->latest('id')->limit(40)->get();
        $unread = $list->whereNull('read_at')->pluck('id');
        // Beim Ansehen als gelesen markieren (die Markierung "neu" bleibt für diesen Aufruf sichtbar)
        if ($unread->isNotEmpty()) $org->feedbacks()->whereIn('id', $unread)->update(['read_at' => now()]);

        return view('livewire.guests', [
            'org' => $org,
            'count' => $count,
            'avg' => $count ? round((float) (clone $all)->avg('rating'), 1) : null,
            'dist' => $dist,
            'list' => $list,
            'unread' => $unread->all(),
            'cards' => $org->stampCards()->where('total_stamps', '>', 0)->count(),
            'stamps' => (int) $org->stampCards()->sum('total_stamps'),
            'redeemed' => (int) $org->stampCards()->sum('redeemed'),
        ])->title(__('Gäste'));
    }
}
