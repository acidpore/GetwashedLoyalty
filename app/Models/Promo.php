<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    public const TYPES = [
        'percent' => 'Persen (%)',
        'amount' => 'Nominal (Rp)',
    ];

    protected $fillable = ['name', 'type', 'value', 'is_active'];

    protected $casts = [
        'value' => 'integer',
        'is_active' => 'boolean',
    ];

    public function discountFor(int $subtotal): int
    {
        return min($subtotal, $this->type === 'percent'
            ? intdiv($subtotal * min(100, $this->value), 100)
            : $this->value);
    }

    public function label(): string
    {
        return $this->type === 'percent' ? "{$this->value}%" : 'Rp' . number_format($this->value, 0, ',', '.');
    }
}
