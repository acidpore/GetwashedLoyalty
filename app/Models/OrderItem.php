<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'product_id', 'name', 'price', 'qty', 'options', 'note'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
