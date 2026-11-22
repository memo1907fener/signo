<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name', 'industry', 'plan', 'trial_ends_at', 'subscription_status', 'subscription_ends_at',
        'stripe_customer_id', 'stripe_subscription_id', 'locked_at', 'lock_reason', 'signup_ip', 'reminder_stage',
        'ordering_enabled', 'ordering_mode', 'ordering_note', 'kitchen_token',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'locked_at' => 'datetime',
            'ordering_enabled' => 'boolean',
        ];
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function owners() { return $this->users()->wherePivot('role', 'owner'); }
    public function invitations(): HasMany { return $this->hasMany(Invitation::class); }

    /** Betrieb samt Inhalten löschen; Benutzer ohne weiteren Betrieb werden mitgelöscht. */
    public function deleteCompletely(): void
    {
        \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('org/' . $this->id);
        $members = $this->users()->get();
        $this->screens()->delete();
        $this->users()->detach();
        foreach ($members as $u) {
            $other = $u->organizations()->first();
            if ($other) {
                if ($u->organization_id === $this->id) $u->forceFill(['organization_id' => $other->id])->save();
            } else {
                $u->delete();
            }
        }
        $this->delete();
    }
    public function pages(): HasMany { return $this->hasMany(Page::class)->orderBy('position')->orderBy('id'); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function screens(): HasMany { return $this->hasMany(Screen::class)->orderBy('name'); }

    public function hasSubscription(): bool
    {
        if (! $this->stripe_subscription_id) return false;
        if (in_array($this->subscription_status, ['active', 'trialing', 'past_due'], true)) return true;
        // gekündigt, aber bezahlte Zeit läuft noch
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    public function onTrial(): bool
    {
        // Wer schon einmal ein Abo hatte, bekommt keine Testphase zurück
        return ! $this->stripe_subscription_id && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function trialDaysLeft(): int
    {
        if (! $this->trial_ends_at) return 0;
        return max(0, (int) ceil(now()->diffInHours($this->trial_ends_at, false) / 24));
    }

    /** Darf Inhalte ausspielen (Bildschirm, QR-Seite)? */
    public function isActive(): bool
    {
        if ($this->locked_at) return false;
        return $this->hasSubscription() || $this->onTrial();
    }

    public function screenLimit(): int
    {
        if ($this->hasSubscription()) {
            return (int) (config("signo.plans.{$this->plan}.screens") ?? 0);
        }
        return $this->onTrial() ? (int) config('signo.trial_screens') : 0;
    }

    public function planName(): string
    {
        if ($this->hasSubscription()) return config("signo.plans.{$this->plan}.name", 'Abo');
        if ($this->onTrial()) return 'Testphase';
        return 'Kein Paket';
    }

    public function industryLabel(): string
    {
        return config("signo.industries.{$this->industry}.label", 'Sonstige');
    }
}
