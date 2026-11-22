<?php

namespace App\Livewire\Admin;

use App\Models\Organization;
use App\Models\Screen;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin')]
class Organizations extends Component
{
    use WithPagination;

    public string $q = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->is_admin, 403);
    }

    public function updatingQ(): void { $this->resetPage(); }

    public function extend(int $id, int $days = 14): void
    {
        $org = Organization::findOrFail($id);
        $base = $org->trial_ends_at && $org->trial_ends_at->isFuture() ? $org->trial_ends_at : now();
        $org->update(['trial_ends_at' => $base->copy()->addDays($days)]);
    }

    public function toggleLock(int $id): void
    {
        $org = Organization::findOrFail($id);
        $org->update($org->locked_at ? ['locked_at' => null, 'lock_reason' => null] : ['locked_at' => now(), 'lock_reason' => 'Admin']);
    }

    public function remove(int $id): void
    {
        $org = Organization::findOrFail($id);
        abort_if($org->id === auth()->user()->organization_id, 400);
        Storage::disk('public')->deleteDirectory('org/' . $org->id);
        $org->screens()->delete();
        $org->users()->delete();
        $org->delete();
    }

    public function render()
    {
        abort_unless(auth()->user()->is_admin, 403);
        $orgs = Organization::query()
            ->with(['users:id,organization_id,email,name,last_login_at'])
            ->withCount(['pages', 'screens'])
            ->when($this->q, fn ($q) => $q->where('name', 'like', "%{$this->q}%")
                ->orWhereHas('users', fn ($u) => $u->where('email', 'like', "%{$this->q}%")))
            ->latest()->paginate(25);

        return view('livewire.admin.organizations', [
            'orgs' => $orgs,
            'stats' => [
                'Kunden' => Organization::count(),
                'In Testphase' => Organization::whereNull('stripe_subscription_id')->where('trial_ends_at', '>', now())->count(),
                'Zahlend' => Organization::whereIn('subscription_status', ['active', 'trialing', 'past_due'])->count(),
                'Bildschirme online' => Screen::whereNotNull('organization_id')->where('last_seen_at', '>', now()->subMinutes(3))->count(),
            ],
        ]);
    }
}
