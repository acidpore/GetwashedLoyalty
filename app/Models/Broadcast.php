<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Broadcast extends Model
{
    const COST_PER_MESSAGE = 600;

    protected $fillable = [
        'message',
        'target_filter',
        'total_recipients',
        'estimated_cost',
        'sent_count',
        'failed_count',
        'status',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function calculateCost(): int
    {
        return $this->total_recipients * self::COST_PER_MESSAGE;
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
        
        return ($this->sent_count / $this->total_recipients) * 100;
    }

    public function incrementSent(): void
    {
        $this->increment('sent_count');
        
        if ($this->sent_count >= $this->total_recipients) {
            $this->update(['status' => 'completed']);
        }
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
    }
}
