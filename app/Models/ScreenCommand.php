<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenCommand extends Model
{
    protected $fillable = ['screen_id', 'command', 'acked_at', 'expires_at'];

    protected function casts(): array
    {
        return ['acked_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function screen(): BelongsTo { return $this->belongsTo(Screen::class); }
}
