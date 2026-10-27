<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = ['name', 'unit', 'stock', 'min_stock'];

    protected $casts = [
        'stock' => 'float',
        'min_stock' => 'float',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(IngredientMovement::class);
    }

    public function scopeLow(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'min_stock');
    }

    /** Manual restock or correction, logged. */
    public function adjust(float $qty, string $reason, ?int $userId): void
    {
        $this->increment('stock', $qty);
        $this->movements()->create(['qty' => $qty, 'reason' => $reason, 'user_id' => $userId]);
    }
}
