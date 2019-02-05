<?php

namespace Modules\Notification\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    /**
     * @var string
     */
    private $order_id;
    /**
     * @var string
     */
    private $status;

    /**
     * Create a new event instance.
     *
     * @param string $order_id
     * @param string $status
     */
    public function __construct(string $order_id, string $status)
    {
        $this->order_id = $order_id;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->order_id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}
