<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'last_login_at',
        'last_activity_at',
        'last_login_ip',
        'is_banned',
        'banned_at',
        'ban_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'banned_at' => 'datetime',
            'is_banned' => 'boolean',
        ];
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_banned) {
            return false;
        }
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isBanned(): bool
    {
        return $this->is_banned;
    }

    public function ban(?string $reason = null): void
    {
        $this->update([
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => $reason,
        ]);
    }

    public function unban(): void
    {
        $this->update([
            'is_banned' => false,
            'banned_at' => null,
            'ban_reason' => null,
        ]);
    }

    public function recordLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function recordActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public static function findByPhone(string $phone): ?self
    {
        return self::where('phone', $phone)->first();
    }
}

