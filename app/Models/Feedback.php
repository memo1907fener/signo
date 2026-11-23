<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = ['organization_id', 'rating', 'comment', 'contact', 'lang', 'ip', 'read_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'read_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
}
