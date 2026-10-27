<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['ingredient_id', 'qty', 'reason', 'user_id'];

    protected $casts = ['qty' => 'float'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
