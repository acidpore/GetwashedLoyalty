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
        'thresholds',
    ];

    protected $casts = [
        'loyalty_types' => 'array',
        'thresholds' => 'array',
        'is_active' => 'boolean',
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'scan_count' => 'integer',
    ];

    protected $appends = [
        'has_carwash',
        'has_motorwash',
        'has_coffeeshop',
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

    // Accessors for individual loyalty program checkboxes
    public function getHasCarwashAttribute(): bool
    {
        return $this->hasLoyaltyType('carwash');
    }

    public function getHasMotorwashAttribute(): bool
    {
        return $this->hasLoyaltyType('motorwash');
    }

    public function getHasCoffeeshopAttribute(): bool
    {
        return $this->hasLoyaltyType('coffeeshop');
    }

    // Mutators for individual loyalty program checkboxes
    public function setHasCarwashAttribute($value): void
    {
        $types = $this->loyalty_types ?? [];
        if ($value && !in_array('carwash', $types)) {
            $types[] = 'carwash';
        } elseif (!$value) {
            $types = array_values(array_diff($types, ['carwash']));
        }
        $this->setAttribute('loyalty_types', $types);
    }

    public function setHasMotorwashAttribute($value): void
    {
        $types = $this->loyalty_types ?? [];
        if ($value && !in_array('motorwash', $types)) {
            $types[] = 'motorwash';
        } elseif (!$value) {
            $types = array_values(array_diff($types, ['motorwash']));
        }
        $this->setAttribute('loyalty_types', $types);
    }

    public function setHasCoffeeshopAttribute($value): void
    {
        $types = $this->loyalty_types ?? [];
        if ($value && !in_array('coffeeshop', $types)) {
            $types[] = 'coffeeshop';
        } elseif (!$value) {
            $types = array_values(array_diff($types, ['coffeeshop']));
        }
        $this->setAttribute('loyalty_types', $types);
    }

    // Mutator to filter out zero/null thresholds
    public function setThresholdsAttribute($value): void
    {
        if (!is_array($value)) {
            $this->attributes['thresholds'] = null;
            return;
        }

        // Filter out null, 0, and empty string values
        $filtered = array_filter($value, function($threshold) {
            return $threshold !== null && $threshold !== '' && $threshold !== 0 && $threshold !== '0';
        });

        $this->attributes['thresholds'] = !empty($filtered) ? json_encode($filtered) : null;
    }
}
