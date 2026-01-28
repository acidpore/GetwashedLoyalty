<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private ?string $apiUrlReply;
    private ?string $apiUrlPush;
    private ?string $apiToken;
    private ?string $checkinTemplateId;
    private ?string $rewardTemplateId;

    public function __construct()
    {
        $this->apiUrlReply = config('services.whatsapp.api_url_reply');
        $this->apiUrlPush = config('services.whatsapp.api_url_push');
        $this->apiToken = config('services.whatsapp.api_token');
        $this->checkinTemplateId = config('services.whatsapp.checkin_template_id');
        $this->rewardTemplateId = config('services.whatsapp.reward_template_id');
    }

    public function sendMessage(string $phone, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('MaxChat WhatsApp not configured', compact('phone'));
            return false;
        }

        try {
            $response = $this->sendViaReply($phone, $message);
            $this->logResult($response, $phone);

            return $response['success'];

        } catch (\Exception $e) {
            Log::error('MaxChat WhatsApp failed', [
                'phone' => $phone, 
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendLoyaltyNotification(string $phone, string $name, $customer, array $loyaltyTypes, string $dashboardLink): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('MaxChat WhatsApp not configured', compact('phone'));
            return false;
        }

        try {
            // Build message for Reply attempt
            $message = $this->buildLoyaltyMessage($name, $customer, $loyaltyTypes, $dashboardLink);
            
            // ATTEMPT 1: Try sending as Reply Message (Rp 0)
            $replyResponse = $this->sendViaReply($phone, $message);
            
            // SUCCESS: Reply sent successfully
            if ($replyResponse['success']) {
                $this->logResult($replyResponse, $phone, 'reply');
                return true;
            }
            
            // DETECTION: Check if error is session-related
            if ($this->shouldFallbackToTemplate($replyResponse)) {
                Log::info('Reply failed, falling back to template', [
                    'phone' => $phone,
                    'error_code' => $replyResponse['error_code']
                ]);
                
                // FALLBACK: Send as Template Message (Rp 294)
                $templateResponse = $this->sendViaTemplate($phone, $customer, $loyaltyTypes);
                $this->logResult($templateResponse, $phone, 'template');
                
                return $templateResponse['success'];
            }
            
            // Other errors: don't fallback
            $this->logResult($replyResponse, $phone, 'reply');
            return false;

        } catch (\Exception $e) {
            Log::error('MaxChat loyalty notification failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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

    private function isConfigured(): bool
    {
        return !empty($this->apiUrlReply) 
            && !empty($this->apiUrlPush) 
            && !empty($this->apiToken)
            && !empty($this->checkinTemplateId)
            && !empty($this->rewardTemplateId);
    }

    private function logResult(array $response, string $phone, string $method = 'reply'): void
    {
        if ($response['success']) {
            Log::info('MaxChat WhatsApp sent successfully', [
                'phone' => $phone,
                'method' => $method,
                'provider' => 'maxchat'
            ]);
        } else {
            Log::error('MaxChat WhatsApp send failed', [
                'phone' => $phone,
                'method' => $method,
                'provider' => 'maxchat',
                'error' => $response['error'] ?? 'Unknown error',
                'error_code' => $response['error_code'] ?? null
            ]);
        }
    }

    /**
     * Send message via MaxChat Reply endpoint (free within 24h window)
     * 
     * @param string $phone Phone number in international format (e.g., 6281288889999)
     * @param string $message Message text to send
     * @return array ['success' => bool, 'error' => string|null, 'error_code' => string|null]
     */
    private function sendViaReply(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                    'Authorization' => $this->apiToken,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrlReply, [
                    'channel' => 'whatsapp',
                    'msgType' => 'text',
                    'to' => $phone,
                    'text' => $message,
                ]);

            // Handle successful response (200 OK)
            if ($response->successful()) {
                return ['success' => true];
            }

            // Handle 400 Bad Request (validation errors)
            if ($response->status() === 400) {
                $errorMessage = $response->json('message');
                $errorDetail = is_array($errorMessage) ? implode(', ', $errorMessage) : $errorMessage;
                
                return [
                    'success' => false,
                    'error' => "Validation error: {$errorDetail}",
                    'error_code' => 'VALIDATION_ERROR'
                ];
            }

            // Handle 500 Internal Server Error (business logic errors)
            if ($response->status() === 500) {
                $errorCode = $response->json('code');
                $errorMessage = $response->json('message');

                // Log specific MaxChat error codes
                if ($errorCode === 'MORE_24_HOURS') {
                    Log::warning('MaxChat: Message outside 24-hour window', [
                        'phone' => $phone,
                        'error_code' => $errorCode
                    ]);
                } elseif ($errorCode === 'CHAT_NOT_FOUND') {
                    Log::warning('MaxChat: Chat session not found', [
                        'phone' => $phone,
                        'error_code' => $errorCode
                    ]);
                }

                return [
                    'success' => false,
                    'error' => $errorMessage ?? 'MaxChat service error',
                    'error_code' => $errorCode
                ];
            }

            // Handle other HTTP errors
            return [
                'success' => false,
                'error' => $response->body() ?: 'Unknown HTTP error',
                'error_code' => 'HTTP_' . $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }

    /**
     * Check if reply error should trigger template fallback
     */
    private function shouldFallbackToTemplate(array $response): bool
    {
        // Session-related errors that indicate template fallback needed
        return !$response['success'] && in_array($response['error_code'], [
            'MORE_24_HOURS',    // Outside 24-hour window
            'CHAT_NOT_FOUND',   // No active chat session
            'HTTP_400',         // May indicate no active session
        ]);
    }

    /**
     * Send message via MaxChat Template endpoint (paid, opens new session)
     */
    private function sendViaTemplate(string $phone, $customer, array $loyaltyTypes): array
    {
        try {
            // Detect reward status to select appropriate template
            $hasReward = collect($loyaltyTypes)
                ->some(fn($type) => $customer->hasReward($type));
            
            // Select template ID based on scenario
            $templateId = $hasReward 
                ? $this->rewardTemplateId 
                : $this->checkinTemplateId;
            
            // Build template parameters
            $params = $this->buildTemplateParameters($customer, $loyaltyTypes, $hasReward);
            
            // POST to /messages/push with templateId
            $response = Http::withHeaders([
                    'Authorization' => $this->apiToken,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrlPush, [
                    'to' => $phone,
                    'msgType' => 'text',
                    'templateId' => $templateId,
                    'values' => [
                        'body' => $params
                    ]
                ]);

            // Handle successful response (200 OK)
            if ($response->successful()) {
                return ['success' => true];
            }

            // Handle 400 Bad Request (validation errors)
            if ($response->status() === 400) {
                $errorMessage = $response->json('message');
                $errorDetail = is_array($errorMessage) ? implode(', ', $errorMessage) : $errorMessage;
                
                return [
                    'success' => false,
                    'error' => "Template validation error: {$errorDetail}",
                    'error_code' => 'TEMPLATE_VALIDATION_ERROR'
                ];
            }

            // Handle 500 Internal Server Error
            if ($response->status() === 500) {
                $errorCode = $response->json('code');
                $errorMessage = $response->json('message');

                return [
                    'success' => false,
                    'error' => $errorMessage ?? 'Template service error',
                    'error_code' => $errorCode ?? 'TEMPLATE_SERVICE_ERROR'
                ];
            }

            // Handle other HTTP errors
            return [
                'success' => false,
                'error' => $response->body() ?: 'Unknown template error',
                'error_code' => 'HTTP_' . $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'TEMPLATE_EXCEPTION'
            ];
        }
    }

    /**
     * Build template parameters for MaxChat HSM
     */
    private function buildTemplateParameters($customer, array $loyaltyTypes, bool $hasReward): array
    {
        $userName = $customer->user->name;
        $dashboardLink = $customer->generateMagicLink();
        
        if ($hasReward) {
            // Reward template parameters
            $content = $this->buildRewardContentForTemplate($customer, $loyaltyTypes);
            
            return [
                ['index' => 1, 'type' => 'text', 'text' => $this->sanitizeTemplateText($userName)],
                ['index' => 2, 'type' => 'text', 'text' => $this->sanitizeTemplateText($content)],
                ['index' => 3, 'type' => 'text', 'text' => $this->sanitizeTemplateText($dashboardLink)],
            ];
        }
        
        // Check-in template parameters
        $content = $this->buildProgressContentForTemplate($customer, $loyaltyTypes);
        
        return [
            ['index' => 1, 'type' => 'text', 'text' => $this->sanitizeTemplateText($userName)],
            ['index' => 2, 'type' => 'text', 'text' => $this->sanitizeTemplateText($content)],
            ['index' => 3, 'type' => 'text', 'text' => $this->sanitizeTemplateText($dashboardLink)],
        ];
    }

    private function buildRewardContentForTemplate($customer, array $loyaltyTypes): string
    {
        $rewards = [];
        
        foreach ($loyaltyTypes as $type) {
            if ($customer->hasReward($type)) {
                $rewards[] = match($type) {
                    'carwash' => '🚗 ' . \App\Models\SystemSetting::carwashRewardMessage(),
                    'motorwash' => '🏍️ ' . \App\Models\SystemSetting::motorwashRewardMessage(),
                    'coffeeshop' => '☕ ' . \App\Models\SystemSetting::coffeeshopRewardMessage(),
                };
            }
        }
        
        $rewardCount = count($rewards);
        if ($rewardCount === 0) {
            return 'Reward tersedia!';
        }
        
        $title = $rewardCount > 1 ? '🎊 MULTIPLE REWARDS!' : '';
        $rewardList = implode("\n", $rewards);
        
        return $title ? "{$title}\n\n{$rewardList}" : $rewardList;
    }

    private function buildProgressContentForTemplate($customer, array $loyaltyTypes): string
    {
        $carwashThreshold = \App\Models\SystemSetting::carwashRewardThreshold();
        $motorwashThreshold = \App\Models\SystemSetting::motorwashRewardThreshold();
        $coffeeshopThreshold = \App\Models\SystemSetting::coffeeshopRewardThreshold();
        
        $programCount = count($loyaltyTypes);

        if ($programCount === 1) {
            $type = $loyaltyTypes[0];
            return match($type) {
                'carwash' => $this->buildCarwashProgressContent($customer, $carwashThreshold),
                'motorwash' => $this->buildMotorwashProgressContent($customer, $motorwashThreshold),
                'coffeeshop' => $this->buildCoffeeshopProgressContent($customer, $coffeeshopThreshold),
                default => 'Poin Anda telah diperbarui.',
            };
        }

        // Multi-program progress
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
        
        return implode("\n", $lines);
    }

    private function buildCarwashProgressContent($customer, int $threshold): string
    {
        $points = $customer->carwash_points;
        $remaining = max(0, $threshold - $points);
        return "Poin Cuci Mobil: {$points}/{$threshold} 🚗\nKumpulkan {$remaining} poin lagi untuk DISKON!";
    }

    private function buildMotorwashProgressContent($customer, int $threshold): string
    {
        $points = $customer->motorwash_points;
        $remaining = max(0, $threshold - $points);
        return "Poin Cuci Motor: {$points}/{$threshold} 🏍️\nKumpulkan {$remaining} poin lagi untuk DISKON!";
    }

    private function buildCoffeeshopProgressContent($customer, int $threshold): string
    {
        $points = $customer->coffeeshop_points;
        $remaining = max(0, $threshold - $points);
        return "Poin Coffee Shop: {$points}/{$threshold} ☕\nKumpulkan {$remaining} poin lagi untuk GRATIS KOPI!";
    }

    /**
     * Sanitize text for WhatsApp templates by removing newlines and tabs
     * MaxChat template API doesn't allow \n or \t characters
     */
    private function sanitizeTemplateText(string $text): string
    {
        // Replace newlines with spaces
        $text = str_replace(["\n", "\r\n", "\r"], ' ', $text);
        
        // Replace tabs with spaces
        $text = str_replace("\t", ' ', $text);
        
        // Remove multiple consecutive spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim leading/trailing spaces
        return trim($text);
    }
}
