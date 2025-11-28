<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 5;
    private const MAX_ATTEMPTS = 3;
    private const RATE_LIMIT_SECONDS = 3600;

    public function generate(string $phone): ?OtpCode
    {
        if ($this->isRateLimited($phone)) {
            return null;
        }

        $this->invalidatePrevious($phone);
        $otp = $this->createOtp($phone);
        $this->recordAttempt($phone);

        return $otp;
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('otp_code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return false;
        }

        $otp->update(['is_used' => true]);
        return true;
    }

    public function getRemainingTime(string $phone): int
    {
        $key = $this->getRateLimitKey($phone);
        return RateLimiter::availableIn($key);
    }

    public function isRateLimited(string $phone): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->getRateLimitKey($phone),
            self::MAX_ATTEMPTS
        );
    }

    public function cleanup(): int
    {
        return OtpCode::where('expires_at', '<', now()->subDay())->delete();
    }

    private function invalidatePrevious(string $phone): void
    {
        OtpCode::where('phone', $phone)
            ->where('is_used', false)
            ->update(['is_used' => true]);
    }

    private function createOtp(string $phone): OtpCode
    {
        return OtpCode::create([
            'phone' => $phone,
            'otp_code' => $this->generateCode(),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'is_used' => false,
        ]);
    }

    private function generateCode(): string
    {
        return str_pad((string) rand(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function recordAttempt(string $phone): void
    {
        RateLimiter::hit(
            $this->getRateLimitKey($phone),
            self::RATE_LIMIT_SECONDS
        );
    }

    private function getRateLimitKey(string $phone): string
    {
        return "otp-request:{$phone}";
    }
}
