<?php

namespace Modules\Notification\Listeners;

use GuzzleHttp\Client;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Notification\Events\StatusChanged;

class ChangeOrderStatus
{
    /**
     * @var string|null
     */
    private $credential;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->credential = \OpenID::getClientToken();

        $this->client = new Client(['base_uri' => env('API_ENDPOINT')]);
    }

    /**
     * Handle the event.
     *
     * @param  \Modules\Notification\Events\StatusChanged $event
     *
     * @return void
     * @throws \Exception
     */
    public function handle(StatusChanged $event)
    {
        if (!$this->credential)
            throw new \Exception('Not possible to generate the client credential.');

        $this->client->patch('orders/' . $event->getOrderId() . '/status', [
            'headers' => [
                'Authorization' => "Bearer $this->credential",
            ],
            'json'    => ['status' => $event->getStatus()],
        ]);
    }
}
