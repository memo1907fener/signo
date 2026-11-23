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
        'brand', 'notice', 'public_token', 'feedback_enabled', 'google_review_url', 'loyalty', 'offline_alerts',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'locked_at' => 'datetime',
            'ordering_enabled' => 'boolean',
            'brand' => 'array',
            'notice' => 'array',
            'loyalty' => 'array',
            'feedback_enabled' => 'boolean',
            'offline_alerts' => 'boolean',
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
    public function feedbacks(): HasMany { return $this->hasMany(Feedback::class); }
    public function stampCards(): HasMany { return $this->hasMany(StampCard::class); }

    // ---------- Marke ----------
    public function brandAccent(): ?string
    {
        $c = $this->brand['accent'] ?? null;
        return is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : null;
    }

    public function logoUrl(): ?string
    {
        return Page::fileUrl($this->brand['logo'] ?? null);
    }

    public function showsLogo(): bool
    {
        return ! empty($this->brand['logo']) && ($this->brand['show_logo'] ?? true);
    }

    // ---------- Sofortmeldung ----------
    /** Aktive Eilmeldung oder null. */
    public function activeNotice(): ?array
    {
        $n = $this->notice;
        if (! $n || trim((string) ($n['text'] ?? '')) === '') return null;
        if (! empty($n['until']) && now()->gte(\Illuminate\Support\Carbon::parse($n['until']))) return null;
        return ['text' => $n['text'], 'full' => (bool) ($n['full'] ?? false), 'until' => $n['until'] ?? null];
    }

    // ---------- Gäste: Feedback und Stempelkarte ----------
    /** Geheimer Teil der Gäste-Links (wird bei Bedarf erzeugt). */
    public function guestToken(): string
    {
        if (! $this->public_token) {
            do { $t = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)); } while (static::where('public_token', $t)->exists());
            $this->forceFill(['public_token' => $t])->save();
        }
        return $this->public_token;
    }

    public function feedbackUrl(): string { return url('/f/' . $this->guestToken()); }
    public function stampUrl(): string { return url('/s/' . $this->guestToken()); }

    public function loyaltyOn(): bool
    {
        return ! empty($this->loyalty['enabled']) && ! empty($this->loyalty['pin']);
    }

    public function loyaltyGoal(): int
    {
        return max(3, min(20, (int) ($this->loyalty['goal'] ?? 10)));
    }
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
        if ($this->hasSubscription()) return __(config("signo.plans.{$this->plan}.name", 'Abo'));
        if ($this->onTrial()) return __('Testphase');
        return __('Kein Paket');
    }

    public function industryLabel(): string
    {
        return config("signo.industries.{$this->industry}.label", 'Sonstige');   // bewusst Deutsch: geht an die KI
    }
}
