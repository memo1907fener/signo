<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Screen extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'token', 'pair_code', 'device_id', 'paired_at', 'last_seen_at',
        'resolution', 'client', 'app_version', 'ip', 'show_all', 'seconds', 'always_on', 'on_time', 'off_time', 'days',
        'screenshot_path', 'screenshot_at', 'offline_notified_at',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'screenshot_at' => 'datetime',
            'offline_notified_at' => 'datetime',
            'always_on' => 'boolean',
            'show_all' => 'boolean',
            'days' => 'array',
            'seconds' => 'integer',
            'app_version' => 'integer',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class)->withPivot('position')->orderByPivot('position');
    }

    public function commands(): HasMany { return $this->hasMany(ScreenCommand::class); }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(3));
    }

    /** Liegt der Zeitpunkt außerhalb der Betriebszeiten? */
    public function isSleeping(?Carbon $at = null): bool
    {
        if ($this->always_on || ! $this->on_time || ! $this->off_time) return false;
        $at = ($at ?? now())->copy()->timezone(config('app.timezone'));
        $days = $this->days ?: [1, 2, 3, 4, 5, 6, 7];
        $hm = $at->format('H:i');
        $on = $this->on_time; $off = $this->off_time;

        if ($on < $off) {
            // z. B. 08:00–20:00 am selben Tag
            if (! in_array($at->dayOfWeekIso, $days)) return true;
            return ! ($hm >= $on && $hm < $off);
        }
        // über Mitternacht, z. B. 18:00–02:00
        if ($hm >= $on) return ! in_array($at->dayOfWeekIso, $days);
        if ($hm < $off) return ! in_array($at->copy()->subDay()->dayOfWeekIso, $days);
        return true;
    }

    public function statusLabel(): string
    {
        if (! $this->last_seen_at) return __('Noch nie verbunden');
        if (! $this->isOnline()) return __('Offline seit :time', ['time' => $this->last_seen_at->diffForHumans(null, true)]);
        if ($this->isSleeping()) return __('Ruhezeit');
        return 'Online';
    }

    public function hoursLabel(): string
    {
        if ($this->always_on || ! $this->on_time) return __('Immer an');
        $names = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];
        $days = $this->days ?: [1, 2, 3, 4, 5, 6, 7];
        $d = count($days) === 7 ? __('täglich') : implode(', ', array_map(fn ($x) => $names[$x] ?? $x, $days));
        return "{$this->on_time}–{$this->off_time} · $d";
    }
}
