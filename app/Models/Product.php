<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    public const CATEGORIES = [
        'kopi' => 'Kopi',
        'non-kopi' => 'Non-Kopi',
        'makanan' => 'Makanan',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = ['name', 'category', 'price', 'image', 'options', 'is_active', 'sort'];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'options' => 'array',
    ];

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)->withPivot('qty');
    }

    /** Same rows as ingredients(), as models, so a Filament repeater can edit them. */
    public function recipe(): HasMany
    {
        return $this->hasMany(IngredientProduct::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    /**
     * Turns the till's picks ([groupIndex, choiceIndex] pairs) into a label and
     * a surcharge. Unknown picks are dropped; a single-choice group keeps one.
     *
     * @return array{0:string, 1:int}
     */
    public function resolveOptions(array $picks): array
    {
        $labels = [];
        $extra = 0;
        $taken = [];

        foreach ($picks as $pick) {
            [$g, $c] = array_map('intval', array_values((array) $pick) + [0, 0]);
            $group = $this->options[$g] ?? null;
            $choice = $group['choices'][$c] ?? null;

            if (! $choice || isset($taken["$g:$c"]) || (empty($group['multiple']) && isset($taken[$g]))) {
                continue;
            }

            $taken["$g:$c"] = $taken[$g] = true;
            $labels[] = $choice['name'];
            $extra += max(0, (int) ($choice['price'] ?? 0));
        }

        return [implode(', ', $labels), $extra];
    }

    /** Deducts the recipe for $qty portions; a negative $qty puts it back. Stock may go below 0. */
    public function consumeStock(int $qty): void
    {
        foreach ($this->ingredients as $ingredient) {
            $ingredient->decrement('stock', $ingredient->pivot->qty * $qty);
        }
    }
}
