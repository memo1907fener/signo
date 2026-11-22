<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Organization;
use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Bestellungen: im Kundenbereich (mit Einstellungen) und als Küchenbildschirm über geheimen Link.
 */
class Orders extends Component
{
    #[Locked]
    public ?string $token = null;
    #[Locked]
    public bool $kitchen = false;

    public bool $enabled = false;
    public string $mode = 'pickup';
    public string $note = '';
    public string $tableQr = '';
    public ?string $saved = null;

    public function mount(?string $token = null): void
    {
        if ($token) {
            $this->token = $token;
            $this->kitchen = true;
            abort_unless(Organization::where('kitchen_token', $token)->exists(), 404);
        } else {
            abort_unless(auth()->check(), 403);
        }
        $org = $this->org();
        $this->enabled = $org->ordering_enabled;
        $this->mode = $org->ordering_mode ?: 'pickup';
        $this->note = (string) $org->ordering_note;
    }

    protected function org(): Organization
    {
        if ($this->token) return Organization::where('kitchen_token', $this->token)->firstOrFail();
        $org = auth()->user()?->organization;
        abort_unless($org, 403);
        return $org;
    }

    public function saveSettings(): void
    {
        abort_if($this->kitchen, 403);
        $this->validate(['mode' => 'required|in:pickup,table', 'note' => 'nullable|string|max:200']);
        $org = $this->org();
        $org->update([
            'ordering_enabled' => $this->enabled,
            'ordering_mode' => $this->mode,
            'ordering_note' => trim($this->note) ?: null,
            'kitchen_token' => $org->kitchen_token ?: Str::random(32),
        ]);
        // Beim ersten Einschalten: Speise-/Preislisten gleich bestellbar machen
        if ($this->enabled && ! $org->pages()->where('orderable', true)->exists()) {
            $org->pages()->where('template', 'list')->update(['orderable' => true]);
        }
        $this->saved = now()->format('H:i:s');
    }

    public function updatedEnabled(): void { $this->saveSettings(); }
    public function updatedMode(): void { $this->saveSettings(); }

    public function newToken(): void
    {
        abort_if($this->kitchen, 403);
        $this->org()->update(['kitchen_token' => Str::random(32)]);
    }

    public function createBoardPage()
    {
        abort_if($this->kitchen, 403);
        $org = $this->org();
        $page = Page::create([
            'organization_id' => $org->id,
            'title' => 'Abholanzeige',
            'template' => 'orders',
            'theme' => 'dark',
            'accent' => 'green',
            'data' => [],
            'is_public' => false,
            'position' => (int) $org->pages()->max('position') + 1,
        ]);
        session()->flash('ok', 'Die Seite „Abholanzeige“ wurde angelegt. Wählen Sie sie bei einem Bildschirm aus.');
        return $this->redirectRoute('pages.index', navigate: true);
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(array_key_exists($status, Order::STATUS), 400);
        $order = Order::where('organization_id', $this->org()->id)->findOrFail($id);
        $order->update(['status' => $status, 'ready_at' => $status === 'ready' ? now() : $order->ready_at]);
    }

    public function render()
    {
        $org = $this->org();
        $orders = $org->orders()->where('created_at', '>', now()->subHours(16))
            ->whereIn('status', ['new', 'preparing', 'ready'])->orderBy('id')->get();
        $done = $org->orders()->whereDate('created_at', today())->whereIn('status', ['done', 'canceled'])->latest('id')->limit(30)->get();

        $view = view('livewire.orders', [
            'org' => $org,
            'columns' => [
                'new' => $orders->where('status', 'new'),
                'preparing' => $orders->where('status', 'preparing'),
                'ready' => $orders->where('status', 'ready'),
            ],
            'done' => $done,
            'today' => $org->orders()->whereDate('created_at', today())->where('status', '!=', 'canceled')->sum('total_cents'),
            'todayCount' => $org->orders()->whereDate('created_at', today())->where('status', '!=', 'canceled')->count(),
            'lastId' => (int) $orders->max('id'),
            'orderPages' => $org->pages()->where('orderable', true)->get(),
            'hasBoard' => $org->pages()->where('template', 'orders')->exists(),
        ]);

        return $this->kitchen
            ? $view->layout('layouts.kitchen')->title('Küche · ' . $org->name)
            : $view->layout('layouts.app')->title('Bestellungen');
    }
}
