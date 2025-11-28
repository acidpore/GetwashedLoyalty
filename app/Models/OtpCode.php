<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone',
        'otp_code',
        'expires_at',
        'is_used',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Generate a new OTP code for a phone number.
     */
    public static function generate(string $phone): self
    {
        // Invalidate previous OTPs for this phone
        self::where('phone', $phone)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Generate 6-digit OTP
        $otpCode = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create new OTP with 5 minute expiration
        return self::create([
            'phone' => $phone,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
        ]);
    }

    /**
     * Verify OTP code for a phone number.
     */
    public static function verify(string $phone, string $code): bool
    {
        $otp = self::where('phone', $phone)
            ->where('otp_code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($otp) {
            $otp->update(['is_used' => true]);
            return true;
        }

        return false;
    }

    /**
     * Clean up expired OTP codes.
     */
    public static function cleanup(): int
    {
        return self::where('expires_at', '<', now()->subDay())->delete();
    }

    /**
     * Check if OTP is still valid.
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at > now();
    }
}
