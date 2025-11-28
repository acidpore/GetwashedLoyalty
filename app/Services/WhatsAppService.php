<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $provider;
    protected string $apiUrl;
    protected string $apiToken;

    public function __construct()
    {
        $this->provider = config('services.whatsapp.provider', 'fonnte');
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiToken = config('services.whatsapp.api_token');
    }

    /**
     * Send WhatsApp message.
     *
     * @param string $phone Phone number (62xxx format)
     * @param string $message Message content
     * @return bool Success status
     */
    public function sendMessage(string $phone, string $message): bool
    {
        try {
            // Validate config
            if (empty($this->apiUrl) || empty($this->apiToken)) {
                Log::warning('WhatsApp API not configured. Message not sent.', [
                    'phone' => $phone,
                    'message' => $message,
                ]);
                return false;
            }

            $response = match ($this->provider) {
                'fonnte' => $this->sendViaFonnte($phone, $message),
                'wablas' => $this->sendViaWablas($phone, $message),
                'twilio' => $this->sendViaTwilio($phone, $message),
                default => throw new \Exception("Unsupported WhatsApp provider: {$this->provider}"),
            };

            if ($response['success']) {
                Log::info('WhatsApp message sent successfully', [
                    'provider' => $this->provider,
                    'phone' => $phone,
                ]);
                return true;
            }

            Log::error('WhatsApp message failed', [
                'provider' => $this->provider,
                'phone' => $phone,
                'error' => $response['error'] ?? 'Unknown error',
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp service exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send via Fonnte API.
     */
    protected function sendViaFonnte(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post($this->apiUrl, [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => $response->json('detail') ?? $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send via Wablas API.
     */
    protected function sendViaWablas(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post($this->apiUrl, [
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return ['success' => $data['status'] ?? false];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send via Twilio API.
     */
    protected function sendViaTwilio(string $phone, string $message): array
    {
        try {
            $accountSid = config('services.whatsapp.twilio_account_sid');
            $authToken = $this->apiToken;
            $fromNumber = config('services.whatsapp.twilio_from');

            $response = Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->post("{$this->apiUrl}/Accounts/{$accountSid}/Messages.json", [
                    'From' => "whatsapp:{$fromNumber}",
                    'To' => "whatsapp:+{$phone}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send bulk messages (for future use).
     */
    public function sendBulk(array $recipients): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = [
                'phone' => $recipient['phone'],
                'success' => $this->sendMessage($recipient['phone'], $recipient['message']),
            ];
        }
        return $results;
    }
}
