<?php

namespace App\Events;

use App\Models\CustomerReview;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerHasBeenProcessed implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public CustomerReview $customerReview,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('customer-review'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.has.been.processed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array|mixed[]
     */
    public function broadcastWith()
    {
        return [
            'user' => [
                'id' => $this->customerReview->id,
                'sent' => $this->customerReview->sent,
                'reason' => $this->customerReview->reason
            ]
        ];
    }
}
