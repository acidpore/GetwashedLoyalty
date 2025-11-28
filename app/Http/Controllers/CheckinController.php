<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\VisitHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckinController extends Controller
{
    /**
     * Display the check-in form.
     */
    public function index()
    {
        return view('checkin');
    }

    /**
     * Process customer check-in and auto-registration.
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'phone' => 'required|string|min:10|max:15',
        ]);

        try {
            // Normalize phone number (remove spaces, dashes, convert to 62 format)
            $normalizedPhone = $this->normalizePhone($validated['phone']);

            // Rate limiting: Check if same IP+phone checked in within last hour
            if ($this->isRecentCheckIn($normalizedPhone, $request->ip())) {
                return back()->with('error', 'Anda sudah check-in dalam 1 jam terakhir. Silakan coba lagi nanti.');
            }

            DB::beginTransaction();

            // Find or create user
            $user = User::firstOrCreate(
                ['phone' => $normalizedPhone],
                [
                    'name' => $validated['name'],
                    'role' => 'customer',
                    'password' => null, // Passwordless customer
                ]
            );

            // Update name if changed
            if ($user->name !== $validated['name']) {
                $user->update(['name' => $validated['name']]);
            }

            // Find or create customer profile
            $customer = Customer::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'current_points' => 0,
                    'total_visits' => 0,
                ]
            );

            // Add points and update visit count
            $customer->current_points += 1;
            $customer->total_visits += 1;
            $customer->last_visit_at = now();
            $customer->save();

            // Record visit history
            VisitHistory::create([
                'customer_id' => $customer->id,
                'points_earned' => 1,
                'visited_at' => now(),
                'ip_address' => $request->ip(),
            ]);

            // Check reward logic
            $message = $this->generateWhatsAppMessage($user->name, $customer->current_points);
            
            // If customer earned reward (5 points), reset points
            if ($customer->current_points >= 5) {
                $pointsBeforeReset = $customer->current_points;
                $customer->current_points = 0;
                $customer->save();
                
                // Send WhatsApp notification (reward message)
                $this->sendWhatsApp($normalizedPhone, $message);
                
                DB::commit();
                
                return redirect()->route('success', [
                    'points' => $pointsBeforeReset,
                    'name' => $user->name,
                    'reward' => true,
                ]);
            } else {
                // Send WhatsApp notification (progress message)
                $this->sendWhatsApp($normalizedPhone, $message);
                
                DB::commit();
                
                return redirect()->route('success', [
                    'points' => $customer->current_points,
                    'name' => $user->name,
                    'reward' => false,
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check-in failed: ' . $e->getMessage());
            
            return back()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    /**
     * Normalize phone number to 62xxx format.
     */
    private function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert 08xxx to 628xxx
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        // Add 62 prefix if not present
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Check if customer already checked in recently (anti-spam).
     */
    private function isRecentCheckIn(string $phone, string $ip): bool
    {
        $user = User::where('phone', $phone)->first();
        
        if (!$user || !$user->customer) {
            return false;
        }

        $recentVisit = VisitHistory::where('customer_id', $user->customer->id)
            ->where('ip_address', $ip)
            ->where('visited_at', '>=', now()->subHour())
            ->exists();

        return $recentVisit;
    }

    /**
     * Generate WhatsApp message based on points.
     */
    private function generateWhatsAppMessage(string $name, int $points): string
    {
        if ($points >= 5) {
            return "🎉 SELAMAT {$name}!\n\n" .
                   "Kamu telah mengumpulkan 5 poin dan berhak mendapat DISKON!\n\n" .
                   "Tunjukkan pesan ini ke kasir untuk klaim reward kamu.\n\n" .
                   "Terima kasih sudah setia dengan Getwashed! 🚗✨";
        } else {
            $remaining = 5 - $points;
            return "Halo {$name}! 👋\n\n" .
                   "Terima kasih telah mencuci di Getwashed.\n" .
                   "Poin kamu sekarang: {$points}/5\n\n" .
                   "Kumpulkan {$remaining} poin lagi untuk mendapat DISKON! 🎁\n\n" .
                   "Sampai jumpa lagi! 🚗";
        }
    }

    /**
     * Send WhatsApp notification.
     */
    private function sendWhatsApp(string $phone, string $message): void
    {
        $whatsappService = app(\App\Services\WhatsAppService::class);
        $whatsappService->sendMessage($phone, $message);
    }
}
