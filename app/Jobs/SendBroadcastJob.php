<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\Customer;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public Broadcast $broadcast,
        public Customer $customer,
        public string $message
    ) {}

    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            $personalizedMessage = $this->personalizeMessage($this->message, $this->customer);
            
            $success = $whatsappService->sendMessage(
                $this->customer->user->phone,
                $personalizedMessage
            );

            if ($success) {
                $this->broadcast->incrementSent();
            } else {
                $this->broadcast->incrementFailed();
            }

        } catch (\Exception $e) {
            $this->broadcast->incrementFailed();
            Log::error('Broadcast job failed', [
                'broadcast_id' => $this->broadcast->id,
                'customer_id' => $this->customer->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function personalizeMessage(string $message, Customer $customer): string
    {
        return str_replace(
            ['{name}', '{points}', '{visits}'],
            [
                $customer->user->name,
                $customer->current_points,
                $customer->total_visits
            ],
            $message
        );
    }
}
