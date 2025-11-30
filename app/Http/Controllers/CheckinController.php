<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\VisitHistory;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckinController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsappService
    ) {}

    public function index()
    {
        return view('checkin');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'phone' => 'required|string|min:10|max:15',
        ]);

        $normalizedPhone = $this->normalizePhone($validated['phone']);

        // Temporarily disabled for testing - allow multiple check-ins
        // if ($this->isRecentCheckIn($normalizedPhone, $request->ip())) {
        //     return back()->with('error', 'Anda sudah check-in dalam 1 jam terakhir.');
        // }


        try {
            DB::beginTransaction();

            $user = $this->findOrCreateUser($normalizedPhone, $validated['name']);
            $customer = $this->findOrCreateCustomer($user->id);
            
            $threshold = SystemSetting::rewardPointsThreshold();
            $hadReward = $customer->current_points >= $threshold;
            
            // If customer had reward, reset points first before adding new point
            if ($hadReward) {
                $customer->resetPoints();
            }
            
            // Now add the new point
            $this->processCheckin($customer, $request->ip());
            
            $hasReward = $customer->current_points >= $threshold;
            $pointsToShow = $customer->current_points;
            
            $this->sendNotification($normalizedPhone, $user->name, $customer->current_points);

            DB::commit();

            return redirect()->route('success', [
                'points' => $pointsToShow,
                'name' => $user->name,
                'reward' => $hasReward,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check-in failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            return '62' . $phone;
        }

        return $phone;
    }

    private function isRecentCheckIn(string $phone, string $ip): bool
    {
        $user = User::where('phone', $phone)->first();
        
        if (!$user?->customer) {
            return false;
        }

        return VisitHistory::where('customer_id', $user->customer->id)
            ->where('ip_address', $ip)
            ->where('visited_at', '>=', now()->subHour())
            ->exists();
    }

    private function findOrCreateUser(string $phone, string $name): User
    {
        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name, 'role' => 'customer', 'password' => null]
        );

        if ($user->name !== $name) {
            $user->update(['name' => $name]);
        }

        return $user;
    }

    private function findOrCreateCustomer(int $userId): Customer
    {
        return Customer::firstOrCreate(
            ['user_id' => $userId],
            ['current_points' => 0, 'total_visits' => 0]
        );
    }

    private function processCheckin(Customer $customer, string $ip): void
    {
        $customer->addPoints();

        VisitHistory::create([
            'customer_id' => $customer->id,
            'points_earned' => 1,
            'visited_at' => now(),
            'ip_address' => $ip,
        ]);
    }

    private function sendNotification(string $phone, string $name, int $points): void
    {
        $threshold = SystemSetting::rewardPointsThreshold();
        $message = $points >= $threshold
            ? "🎉 SELAMAT {$name}!\n\nKamu dapat DISKON!\n\nTunjukkan pesan ini ke kasir.\n\nTerima kasih sudah setia dengan Getwashed! 🚗✨"
            : "Halo {$name}! 👋\n\nTerima kasih telah mencuci di Getwashed.\nPoin kamu: {$points}/{$threshold}\n\nKumpulkan " . ($threshold - $points) . " poin lagi! 🎁\n\nSampai jumpa! 🚗";

        $this->whatsappService->sendMessage($phone, $message);
    }
}
