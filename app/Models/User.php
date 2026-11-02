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
        ];
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' || (in_array($this->role, ['cashier', 'owner']) && $panel->getId() === 'kasir');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Full control of the Latte panel without access to the Getwashed loyalty admin. */
    public function managesLatte(): bool
    {
        // ponytail: client asked on 2026-11-02 for cashiers to match owners for now; drop 'cashier' to restrict them again.
        return in_array($this->role, ['admin', 'owner', 'cashier']);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public static function findByPhone(string $phone): ?self
    {
        return self::where('phone', $phone)->first();
    }
}
