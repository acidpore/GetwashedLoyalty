<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'loyalty_types',
        'qr_type',
        'name',
        'location',
        'is_active',
        'is_used',
        'expires_at',
        'scan_count',
        'created_by',
    ];

    protected $casts = [
        'loyalty_types' => 'array',
        'is_active' => 'boolean',
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'scan_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function incrementScan(): void
    {
        $this->increment('scan_count');

        if ($this->qr_type === 'onetime') {
            $this->update(['is_used' => true]);
        }
    }

    public function getUrlAttribute(): string
    {
        return url("/checkin?code={$this->code}");
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->qr_type === 'onetime' && $this->is_used) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function getLoyaltyTypesString(): string
    {
        if (empty($this->loyalty_types)) {
            return 'None';
        }

        $labels = array_map(function($type) {
            return match($type) {
                'carwash' => 'Cuci Mobil',
                'motorwash' => 'Cuci Motor',
                'coffeeshop' => 'Coffee Shop',
                default => ucfirst($type),
            };
        }, $this->loyalty_types);

        return implode(', ', $labels);
    }

    public function hasLoyaltyType(string $type): bool
    {
        return in_array($type, $this->loyalty_types ?? []);
    }
}
