<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'organization_id', 'is_admin', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /** Aktuell gewählter Betrieb/Standort. */
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }

    /** Alle Betriebe/Standorte, auf die der Benutzer Zugriff hat. */
    public function organizations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withPivot('role')->withTimestamps()->orderBy('name');
    }

    public function roleIn(?Organization $org): ?string
    {
        if (! $org) return null;
        return $this->organizations()->where('organizations.id', $org->id)->first()?->pivot->role;
    }

    public function isOwnerOf(?Organization $org): bool
    {
        return $this->is_admin || $this->roleIn($org) === 'owner';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPassword($token));
    }
}
