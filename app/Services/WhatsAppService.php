<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $provider;
    private string $apiUrl;
    private string $apiToken;

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
                default => throw new \Exception("Unsupported provider: {$this->provider}"),
            };

            $this->logResult($response, $phone);

            return $response['success'];

        } catch (\Exception $e) {
            Log::error('WhatsApp failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendBulk(array $recipients): array
    {
        return array_map(
            fn($r) => ['phone' => $r['phone'], 'success' => $this->sendMessage($r['phone'], $r['message'])],
            $recipients
        );
    }

    private function isConfigured(): bool
    {
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
}
