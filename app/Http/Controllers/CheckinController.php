<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\QrCode;
use App\Models\User;
use App\Models\VisitHistory;
use App\Services\QrCodeService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckinController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsappService,
        private QrCodeService $qrCodeService
    ) {}

    public function index(Request $request)
    {
        $loyaltyTypes = $this->detectLoyaltyTypes($request);
        $qrCode = null;

        if ($request->has('code')) {
            $qrCode = $this->qrCodeService->validateQrCode($request->code);
            
            if (!$qrCode) {
                return redirect()->route('home')->with('error', 'QR Code tidak valid atau sudah kadaluarsa');
            }

            $loyaltyTypes = $qrCode->loyalty_types ?? ['carwash'];
        }

        return view('checkin', compact('loyaltyTypes', 'qrCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'phone' => 'required|string|min:10|max:15',
            'loyalty_types' => 'required|array',
            'loyalty_types.*' => 'in:carwash,motorwash,coffeeshop',
            'qr_code' => 'nullable|string',
        ]);

        $normalizedPhone = $this->normalizePhone($validated['phone']);
        $loyaltyTypes = $validated['loyalty_types'];

        try {
            DB::beginTransaction();

            $user = $this->findOrCreateUser($normalizedPhone, $validated['name']);
            $customer = $this->findOrCreateCustomer($user->id);

            $pointsEarned = 0;
            foreach ($loyaltyTypes as $type) {
                if ($customer->hasReward($type)) {
                    $customer->resetPoints($type);
                }
                $customer->addPoints($type);
                $pointsEarned++;
            }

            VisitHistory::create([
                'customer_id' => $customer->id,
                'loyalty_types' => $loyaltyTypes,
                'points_earned' => $pointsEarned,
                'visited_at' => now(),
                'ip_address' => $request->ip(),
            ]);

            if ($validated['qr_code']) {
                $this->qrCodeService->incrementScan($validated['qr_code']);
            }

            $dashboardLink = $customer->fresh()->generateMagicLink();

            $this->whatsappService->sendLoyaltyNotification(
                $normalizedPhone,
                $user->name,
                $customer->fresh(),
                $loyaltyTypes,
                $dashboardLink
            );

            DB::commit();

            return redirect()->route('success', [
                'name' => $user->name,
                'loyalty_types' => implode(',', $loyaltyTypes),
                'carwash_points' => $customer->carwash_points,
                'motorwash_points' => $customer->motorwash_points,
                'coffeeshop_points' => $customer->coffeeshop_points,
                'carwash_reward' => $customer->hasReward('carwash'),
                'motorwash_reward' => $customer->hasReward('motorwash'),
                'coffeeshop_reward' => $customer->hasReward('coffeeshop'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check-in failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    private function detectLoyaltyTypes(Request $request): array
    {
        if ($request->has('code')) {
            $qr = QrCode::where('code', $request->code)->first();
            return $qr?->loyalty_types ?? ['carwash'];
        }

        $type = $request->get('type', 'carwash');
        return [$type];
    }

    private function processSingleLoyaltyCheckin(Customer $customer, string $type, string $ip): void
    {
        if ($customer->hasReward($type)) {
            $customer->resetPoints($type);
        }

        $customer->addPoints($type);

        VisitHistory::create([
            'customer_id' => $customer->id,
            'loyalty_types' => [$type],
            'points_earned' => 1,
            'visited_at' => now(),
            'ip_address' => $ip,
        ]);
    }

    private function processMultiLoyaltyCheckin(Customer $customer, string $ip): void
    {
        if ($customer->hasReward('carwash')) {
            $customer->resetPoints('carwash');
        }

        if ($customer->hasReward('coffeeshop')) {
            $customer->resetPoints('coffeeshop');
        }

        $customer->addPoints('carwash');
        $customer->addPoints('coffeeshop');

        VisitHistory::create([
            'customer_id' => $customer->id,
            'loyalty_types' => ['carwash', 'coffeeshop'],
            'points_earned' => 2,
            'visited_at' => now(),
            'ip_address' => $ip,
        ]);
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
            [
                'carwash_points' => 0,
                'carwash_total_visits' => 0,
                'motorwash_points' => 0,
                'motorwash_total_visits' => 0,
                'coffeeshop_points' => 0,
                'coffeeshop_total_visits' => 0,
            ]
        );
    }
}
