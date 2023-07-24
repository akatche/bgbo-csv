<?php

namespace App\Events;

use App\Models\CustomerReview;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifyUser implements ShouldBroadcast, ShouldQueue
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
        logger("estoy en notify user");
        return [
            new Channel('customer-review'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.processed';
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
                'batch_id' => $this->customerReview->batch_id,
                'number' => $this->customerReview->customer_number,
                'date' => $this->customerReview->date->toDateTimeString(),
                'name' => $this->customerReview->customer_name,
                'email' => $this->customerReview->original_data['cust_email'],
                'phone' => $this->customerReview->original_data['cust_phone'],
                'type' => ucfirst($this->customerReview->original_data['trans_type']),
                'sent_at' => $this->customerReview->sent_at,
                'sent' => $this->customerReview->sent,
                'sent_by' => ucfirst($this->customerReview->sent_type),
                'reason' => $this->customerReview->reason
            ]
        ];
    }
}
