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

    public function sendLoyaltyNotification(string $phone, string $name, $customer, string $loyaltyType): bool
    {
        $message = $this->buildLoyaltyMessage($name, $customer, $loyaltyType);
        return $this->sendMessage($phone, $message);
    }

    private function buildLoyaltyMessage(string $name, $customer, string $loyaltyType): string
    {
        $carwashThreshold = \App\Models\SystemSetting::carwashRewardThreshold();
        $coffeeshopThreshold = \App\Models\SystemSetting::coffeeshopRewardThreshold();

        $carwashReward = $customer->hasReward('carwash');
        $coffeeshopReward = $customer->hasReward('coffeeshop');

        if ($carwashReward && $coffeeshopReward) {
            return $this->buildBothRewardsMessage($name,$customer, $carwashThreshold, $coffeeshopThreshold);
        }

        if ($carwashReward) {
            return $this->buildCarwashRewardMessage($name, $customer, $coffeeshopThreshold);
        }

        if ($coffeeshopReward) {
            return $this->buildCoffeeshopRewardMessage($name, $customer, $carwashThreshold);
        }

        return match($loyaltyType) {
            'carwash' => $this->buildCarwashProgressMessage($name, $customer, $carwashThreshold),
            'coffeeshop' => $this->buildCoffeeshopProgressMessage($name, $customer, $coffeeshopThreshold),
            'both' => $this->buildBothProgressMessage($name, $customer, $carwashThreshold, $coffeeshopThreshold),
            default => "Halo {$name}! Check-in berhasil. Terima kasih!",
        };
    }

    private function buildCarwashProgressMessage(string $name, $customer, int $threshold): string
    {
        $points = $customer->carwash_points;
        $remaining = $threshold - $points;

        return "Halo {$name}!\n\nCheck-in Car Wash Berhasil!\n\nPoin Car Wash: {$points}/{$threshold}\n\nKumpulkan {$remaining} poin lagi untuk DISKON!\n\nTerima kasih!";
    }

    private function buildCoffeeshopProgressMessage(string $name, $customer, int $threshold): string
    {
        $points = $customer->coffeeshop_points;
        $remaining = $threshold - $points;

        return "Halo {$name}!\n\nCheck-in Coffee Shop Berhasil!\n\nPoin Coffee Shop: {$points}/{$threshold}\n\nKumpulkan {$remaining} poin lagi untuk GRATIS KOPI!\n\nTerima kasih!";
    }

    private function buildBothProgressMessage(string $name, $customer, int $carwashThreshold, int $coffeeshopThreshold): string
    {
        $carwashPoints = $customer->carwash_points;
        $coffeeshopPoints = $customer->coffeeshop_points;

        return "Halo {$name}!\n\nCheck-in Berhasil!\n\nCar Wash: {$carwashPoints}/{$carwashThreshold}\nCoffee Shop: {$coffeeshopPoints}/{$coffeeshopThreshold}\n\nDouble poin!\n\nTerima kasih!";
    }

    private function buildCarwashRewardMessage(string $name, $customer, int $coffeeshopThreshold): string
    {
        $coffeeshopPoints = $customer->coffeeshop_points;
        $message = \App\Models\SystemSetting::carwashRewardMessage();

        return "SELAMAT {$name}!\n\nKamu dapat {$message}!\n\nTunjukkan pesan ini ke kasir.\n\nPoin Car Wash direset ke 0\nPoin Coffee Shop: {$coffeeshopPoints}/{$coffeeshopThreshold}\n\nTerima kasih sudah setia!";
    }

    private function buildCoffeeshopRewardMessage(string $name, $customer, int $carwashThreshold): string
    {
        $carwashPoints = $customer->carwash_points;
        $message = \App\Models\SystemSetting::coffeeshopRewardMessage();

        return "SELAMAT {$name}!\n\nKamu dapat {$message}!\n\nTunjukkan pesan ini ke kasir.\n\nPoin Coffee Shop direset ke 0\nPoin Car Wash: {$carwashPoints}/{$carwashThreshold}\n\nTerima kasih sudah setia!";
    }

    private function buildBothRewardsMessage(string $name, $customer, int $carwashThreshold, int $coffeeshopThreshold): string
    {
        $carwashMessage = \App\Models\SystemSetting::carwashRewardMessage();
        $coffeeshopMessage = \App\Models\SystemSetting::coffeeshopRewardMessage();

        return "DOUBLE REWARD!\n\nSELAMAT {$name}!\n\n{$carwashMessage}!\n{$coffeeshopMessage}!\n\nTunjukkan pesan ini ke kasir untuk klaim BOTH rewards!\n\nKedua poin direset ke 0.\n\nTerima kasih!";
    }

    public function sendBatch(array $messages): array
    {
        if ($this->provider === 'local') {
            return $this->sendBatchViaLocal($messages);
        }

        // Fallback for other providers (sequential)
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
