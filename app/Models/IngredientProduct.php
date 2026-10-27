<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One recipe line: how much of an ingredient one portion of a product uses. */
class IngredientProduct extends Model
{
    protected $table = 'ingredient_product';

    public $timestamps = false;

    protected $fillable = ['product_id', 'ingredient_id', 'qty'];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
