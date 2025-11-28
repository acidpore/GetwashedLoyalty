<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use Illuminate\Console\Command;

class CleanupExpiredOtps extends Command
{
    protected $signature = 'otp:cleanup';
    protected $description = 'Delete expired OTP codes from database';

    public function handle(OtpService $otpService): int
    {
        $deleted = $otpService->cleanup();

        $this->info("Cleaned up {$deleted} expired OTP(s)");

        return self::SUCCESS;
    }
}
