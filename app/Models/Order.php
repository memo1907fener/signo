<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'organization_id', 'page_id', 'number', 'code', 'status', 'customer_name', 'table_no', 'note',
        'items', 'total_cents', 'lang', 'ip', 'ready_at',
    ];

    protected function casts(): array
    {
        return ['items' => 'array', 'ready_at' => 'datetime', 'total_cents' => 'integer', 'number' => 'integer'];
    }

    public const STATUS = [
        'new' => 'Neu',
        'preparing' => 'In Zubereitung',
        'ready' => 'Fertig',
        'done' => 'Abgeholt',
        'canceled' => 'Storniert',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }

    /** Preis aus einem Eintrag lesen ("12,90", "ab 4,50 €"); null wenn nicht bestellbar. */
    public static function parsePrice(?string $price): ?int
    {
        $p = trim((string) $price);
        if ($p === '' || str_contains($p, '%') || str_contains($p, ':')) return null;
        if (! preg_match('/(\d{1,4})(?:[.,](\d{1,2}))?/', $p, $m)) return null;
        $cents = (int) $m[1] * 100 + (isset($m[2]) ? (int) str_pad($m[2], 2, '0') : 0);
        return $cents > 0 ? $cents : null;
    }

    public static function euro(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }
}
