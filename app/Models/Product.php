<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public const CATEGORIES = [
        'kopi' => 'Kopi',
        'non-kopi' => 'Non-Kopi',
        'makanan' => 'Makanan',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = ['name', 'category', 'price', 'is_active', 'sort'];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];
}
