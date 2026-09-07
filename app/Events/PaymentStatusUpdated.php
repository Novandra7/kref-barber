<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $reference,
        public readonly string $status,
        public readonly string $rawBody
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('payment.' . $this->reference),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'reference' => $this->reference,
            'status'    => $this->status,
            'doku_data' => json_decode($this->rawBody, true),
        ];
    }
}