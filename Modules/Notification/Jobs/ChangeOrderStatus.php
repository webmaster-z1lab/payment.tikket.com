<?php

namespace Modules\Notification\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Transaction\Models\Transaction;

class ChangeOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Modules\Transaction\Models\Transaction
     */
    private $transaction;

    /**
     * Create a new job instance.
     *
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction->fresh();
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $credential = \OpenID::getClientToken();

        if (!$credential) {
            throw new \Exception('Not possible to generate the client credential.');
        }

        $client = new Client(['base_uri' => config('app.domains.api')]);

        $client->patch('api/' .config('app.api_version.core'). '/orders/'.$this->transaction->order_id.'/status', [
            'headers' => [
                'Authorization' => "Bearer $credential",
            ],
            'json'    => ['status' => $this->transaction->status],
        ]);
    }
}
