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

    private const PROGRAM_LABELS = [
        'carwash' => 'Cuci Mobil',
        'motorwash' => 'Cuci Motor',
        'coffeeshop' => 'Coffee Shop',
    ];

    /** "Cuci Mobil: 12/30 poin" plus how far the next tier is. */
    private function progressLine($customer, string $type): string
    {
        $label = self::PROGRAM_LABELS[$type] ?? ucfirst($type);
        $points = $customer->getPoints($type);
        $max = \App\Models\SystemSetting::maxPoints($type);
        $next = $customer->nextMilestone($type);

        $line = "{$label}: {$points}/{$max} poin";

        if ($next) {
            $remaining = max(0, $next['at'] - $points);
            $line .= "\nKumpulkan {$remaining} poin lagi untuk {$next['reward']}.";
        }

        return $line;
    }

    /** "Cuci Mobil: Gratis Cuci Mobil" for the reward waiting to be claimed. */
    private function rewardLine($customer, string $type): ?string
    {
        $earned = $customer->earnedMilestone($type);

        if (! $earned) {
            return null;
        }

        $label = self::PROGRAM_LABELS[$type] ?? ucfirst($type);

        return "{$label}: {$earned['reward']} (tercapai di {$earned['at']} poin)";
    }

    private function buildLoyaltyMessage(string $name, $customer, array $loyaltyTypes, string $dashboardLink): string
    {
        $rewards = collect(\App\Models\SystemSetting::LOYALTY_TYPES)
            ->map(fn (string $type) => $this->rewardLine($customer, $type))
            ->filter()
            ->values();

        if ($rewards->isNotEmpty()) {
            return $this->buildRewardMessage($name, $rewards->all(), $dashboardLink);
        }

        return $this->buildProgressMessage($name, $customer, $loyaltyTypes, $dashboardLink);
    }

    private function buildProgressMessage(string $name, $customer, array $loyaltyTypes, string $dashboardLink): string
    {
        $lines = collect($loyaltyTypes)
            ->map(fn (string $type) => $this->progressLine($customer, $type))
            ->all();

        if ($lines === []) {
            return "Halo {$name}!\n\nCheck-in berhasil.\n\nLihat detail poin:\n{$dashboardLink}\n\nTerima kasih!";
        }

        $title = match (count($lines)) {
            1 => 'Check-in Berhasil',
            2 => 'Multi Check-in Berhasil',
            default => 'Triple Check-in Berhasil',
        };

        $pointsText = implode("\n\n", $lines);

        return "Halo {$name}!\n{$title}\n\n{$pointsText}\n\nLihat detail poin:\n{$dashboardLink}\n\nTerima kasih!";
    }

    private function buildRewardMessage(string $name, array $rewards, string $dashboardLink): string
    {
        $title = count($rewards) > 1 ? 'BEBERAPA HADIAH SIAP DITUKAR' : 'SELAMAT, HADIAH SIAP DITUKAR';
        $rewardText = implode("\n", $rewards);

        return "{$title}\n\nSelamat {$name}!\n\nHadiah yang bisa kamu tukar sekarang:\n{$rewardText}\n\nTunjukkan pesan ini ke kasir untuk klaim hadiah.\nPoin kamu berhenti di angka ini sampai hadiah diklaim, jadi klaim dulu supaya bisa lanjut mengumpulkan poin lagi.\n\nLihat detail poin:\n{$dashboardLink}\n\nTerima kasih sudah setia!";
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
        $rewards = collect($loyaltyTypes)
            ->map(fn (string $type) => $this->rewardLine($customer, $type))
            ->filter()
            ->values();

        if ($rewards->isEmpty()) {
            return 'Reward tersedia!';
        }

        $title = $rewards->count() > 1 ? "🎊 MULTIPLE REWARDS!\n\n" : '';

        return $title . $rewards->implode("\n");
    }

    private function buildProgressContentForTemplate($customer, array $loyaltyTypes): string
    {
        $lines = collect($loyaltyTypes)
            ->map(fn (string $type) => $this->progressLine($customer, $type))
            ->all();

        return $lines === [] ? 'Poin Anda telah diperbarui.' : implode("\n\n", $lines);
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
