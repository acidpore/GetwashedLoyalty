<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $provider;
    private ?string $apiUrl;
    private ?string $apiToken;

    public function __construct()
    {
        $this->provider = config('services.whatsapp.provider', 'fonnte');
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiToken = config('services.whatsapp.api_token');
    }

    public function sendMessage(string $phone, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('WhatsApp not configured', compact('phone'));
            return false;
        }

        try {
            $response = match ($this->provider) {
                'fonnte' => $this->sendViaFonnte($phone, $message),
                'wablas' => $this->sendViaWablas($phone, $message),
                'twilio' => $this->sendViaTwilio($phone, $message),
                'local' => $this->sendViaLocal($phone, $message),
                default => throw new \Exception("Unsupported provider: {$this->provider}"),
            };

            $this->logResult($response, $phone);

            return $response['success'];

        } catch (\Exception $e) {
            Log::error('WhatsApp failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendLoyaltyNotification(string $phone, string $name, $customer, array $loyaltyTypes, string $dashboardLink): bool
    {
        $message = $this->buildLoyaltyMessage($name, $customer, $loyaltyTypes, $dashboardLink);
        return $this->sendMessage($phone, $message);
    }

    private function buildLoyaltyMessage(string $name, $customer, array $loyaltyTypes, string $dashboardLink): string
    {
        $carwashThreshold = \App\Models\SystemSetting::carwashRewardThreshold();
        $motorwashThreshold = \App\Models\SystemSetting::motorwashRewardThreshold();
        $coffeeshopThreshold = \App\Models\SystemSetting::coffeeshopRewardThreshold();

        $carwashReward = $customer->hasReward('carwash');
        $motorwashReward = $customer->hasReward('motorwash');
        $coffeeshopReward = $customer->hasReward('coffeeshop');

        $hasAnyReward = $carwashReward || $motorwashReward || $coffeeshopReward;

        if ($hasAnyReward) {
            return $this->buildRewardMessage($name, $customer, $carwashReward, $motorwashReward, $coffeeshopReward, $dashboardLink);
        }

        return $this->buildProgressMessage($name, $customer, $loyaltyTypes, $carwashThreshold, $motorwashThreshold, $coffeeshopThreshold, $dashboardLink);
    }

    private function buildProgressMessage(string $name, $customer, array $loyaltyTypes, int $carwashThreshold, int $motorwashThreshold, int $coffeeshopThreshold, string $dashboardLink): string
    {
        $programCount = count($loyaltyTypes);

        if ($programCount === 1) {
            $type = $loyaltyTypes[0];
            return match($type) {
                'carwash' => $this->buildCarwashProgressMessage($name, $customer, $carwashThreshold, $dashboardLink),
                'motorwash' => $this->buildMotorwashProgressMessage($name, $customer, $motorwashThreshold, $dashboardLink),
                'coffeeshop' => $this->buildCoffeeshopProgressMessage($name, $customer, $coffeeshopThreshold, $dashboardLink),
                default => "Halo {$name}!\n\nCheck-in berhasil!\n\nLihat detail poin:\n👉 {$dashboardLink}\n\nTerima kasih!",
            };
        }

        return $this->buildMultiProgramProgressMessage($name, $customer, $loyaltyTypes, $carwashThreshold, $motorwashThreshold, $coffeeshopThreshold, $dashboardLink);
    }

    private function buildRewardMessage(string $name, $customer, bool $carwashReward, bool $motorwashReward, bool $coffeeshopReward, string $dashboardLink): string
    {
        $rewards = [];
        
        if ($carwashReward) {
            $rewards[] = '🚗 ' . \App\Models\SystemSetting::carwashRewardMessage();
        }
        
        if ($motorwashReward) {
            $rewards[] = '🏍️ ' . \App\Models\SystemSetting::motorwashRewardMessage();
        }
        
        if ($coffeeshopReward) {
            $rewards[] = '☕ ' . \App\Models\SystemSetting::coffeeshopRewardMessage();
        }

        $rewardCount = count($rewards);
        $title = $rewardCount > 1 ? '🎊 MULTIPLE REWARDS! 🎊' : '🎉 SELAMAT! 🎉';
        $rewardText = implode("\n", $rewards);

        return "{$title}\n\nSELAMAT {$name}!\n\n{$rewardText}\n\nTunjukkan pesan ini ke kasir untuk klaim reward!\n\nLihat detail poin:\n👉 {$dashboardLink}\n\nTerima kasih sudah setia! 💙";
    }

    private function buildCarwashProgressMessage(string $name, $customer, int $threshold, string $dashboardLink): string
    {
        $points = $customer->carwash_points;
        $remaining = max(0, $threshold - $points);

        return "Halo {$name}! 👋\n✅ Cuci Mobil Check-in Berhasil!\n\nPoin Cuci Mobil: {$points}/{$threshold} 🚗\nKumpulkan {$remaining} poin lagi untuk DISKON!\n\nLihat detail poin:\n👉 {$dashboardLink}\n\nTerima kasih! 🎉";
    }

    private function buildMotorwashProgressMessage(string $name, $customer, int $threshold, string $dashboardLink): string
    {
        $points = $customer->motorwash_points;
        $remaining = max(0, $threshold - $points);

        return "Halo {$name}! 👋\n✅ Cuci Motor Check-in Berhasil!\n\nPoin Cuci Motor: {$points}/{$threshold} 🏍️\nKumpulkan {$remaining} poin lagi untuk DISKON!\n\nLihat detail poin:\n👉 {$dashboardLink}\n\nTerima kasih! 🎉";
    }

    private function buildCoffeeshopProgressMessage(string $name, $customer, int $threshold, string $dashboardLink): string
    {
        $points = $customer->coffeeshop_points;
        $remaining = max(0, $threshold - $points);

        return "Halo {$name}! 👋\n✅ Coffee Shop Check-in Berhasil!\n\nPoin Coffee Shop: {$points}/{$threshold} ☕\nKumpulkan {$remaining} poin lagi untuk GRATIS KOPI!\n\nLihat detail poin:\n👉 {$dashboardLink}\n\nTerima kasih! 🎉";
    }

    private function buildMultiProgramProgressMessage(string $name, $customer, array $loyaltyTypes, int $carwashThreshold, int $motorwashThreshold, int $coffeeshopThreshold, string $dashboardLink): string
    {
        $lines = [];

        if (in_array('carwash', $loyaltyTypes)) {
            $lines[] = "🚗 Cuci Mobil: {$customer->carwash_points}/{$carwashThreshold}";
        }

        if (in_array('motorwash', $loyaltyTypes)) {
            $lines[] = "🏍️ Cuci Motor: {$customer->motorwash_points}/{$motorwashThreshold}";
        }

        if (in_array('coffeeshop', $loyaltyTypes)) {
            $lines[] = "☕ Coffee Shop: {$customer->coffeeshop_points}/{$coffeeshopThreshold}";
        }

        $programCount = count($lines);
        $title = $programCount >= 3 ? 'Triple Check-in Berhasil!' : 'Multi Check-in Berhasil!';
        $pointsText = implode("\n", $lines);

        return "Halo {$name}! 👋\n✅ {$title}\n\n{$pointsText}\n\nLihat detail poin:\n👉 {$dashboardLink}\n\nAmazing! 🎁 Terima kasih! 🎉";
    }

    public function sendBatch(array $messages): array
    {
        if ($this->provider === 'local') {
            return $this->sendBatchViaLocal($messages);
        }

        return array_map(
            fn($r) => ['phone' => $r['phone'], 'success' => $this->sendMessage($r['phone'], $r['message'])],
            $messages
        );
    }

    private function sendBatchViaLocal(array $messages): array
    {
        try {
            $localUrl = config('services.whatsapp.local_url');
            
            $response = Http::timeout(30)
                ->post("{$localUrl}/send-batch", [
                    'messages' => $messages,
                ]);

            if (!$response->successful()) {
                Log::error('WhatsApp batch failed', ['error' => $response->body()]);
                return [
                    'success' => false,
                    'error' => $response->json('message') ?? 'Local service unavailable'
                ];
            }

            return [
                'success' => true,
                'count' => $response->json('count')
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp batch exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function isConfigured(): bool
    {
        if ($this->provider === 'local') {
            return !empty(config('services.whatsapp.local_url'));
        }
        
        return !empty($this->apiUrl) && !empty($this->apiToken);
    }

    private function logResult(array $response, string $phone): void
    {
        $response['success']
            ? Log::info('WhatsApp sent', ['provider' => $this->provider, 'phone' => $phone])
            : Log::error('WhatsApp failed', ['provider' => $this->provider, 'phone' => $phone, 'error' => $response['error'] ?? 'Unknown']);
    }

    private function sendViaFonnte(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders(['Authorization' => $this->apiToken])
                ->post($this->apiUrl, [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            return $response->successful()
                ? ['success' => true]
                : ['success' => false, 'error' => $response->json('detail') ?? $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendViaWablas(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders(['Authorization' => $this->apiToken])
                ->post($this->apiUrl, compact('phone', 'message'));

            if (!$response->successful()) {
                return ['success' => false, 'error' => $response->json('message') ?? $response->body()];
            }

            return ['success' => $response->json('status', false)];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendViaTwilio(string $phone, string $message): array
    {
        try {
            $sid = config('services.whatsapp.twilio_account_sid');
            $from = config('services.whatsapp.twilio_from');

            $response = Http::asForm()
                ->withBasicAuth($sid, $this->apiToken)
                ->post("{$this->apiUrl}/Accounts/{$sid}/Messages.json", [
                    'From' => "whatsapp:{$from}",
                    'To' => "whatsapp:+{$phone}",
                    'Body' => $message,
                ]);

            return $response->successful()
                ? ['success' => true]
                : ['success' => false, 'error' => $response->json('message') ?? $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendViaLocal(string $phone, string $message): array
    {
        try {
            $localUrl = config('services.whatsapp.local_url');
            
            $response = Http::timeout(10)
                ->post("{$localUrl}/send-message", [
                    'phone' => $phone,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => $response->json('message') ?? 'Local service unavailable'
                ];
            }

            $data = $response->json();
            
            return [
                'success' => $data['success'] ?? false,
                'error' => $data['message'] ?? null
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
