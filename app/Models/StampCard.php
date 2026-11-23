<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampCard extends Model
{
    protected $fillable = ['organization_id', 'code', 'stamps', 'total_stamps', 'redeemed', 'last_stamp_at'];

    protected function casts(): array
    {
        return ['stamps' => 'integer', 'total_stamps' => 'integer', 'redeemed' => 'integer', 'last_stamp_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
}
