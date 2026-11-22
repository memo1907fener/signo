<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $fillable = ['organization_id', 'email', 'role', 'token', 'invited_by', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function inviter(): BelongsTo { return $this->belongsTo(User::class, 'invited_by'); }

    public function url(): string { return route('invitation', $this->token); }
}
