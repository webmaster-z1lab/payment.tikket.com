<?php
/**
 * Created by PhpStorm.
 * User: Felipe
 * Date: 24/01/2019
 * Time: 15:35
 */

namespace Modules\Transaction\Repositories;

use Carbon\Carbon;
use Modules\Notification\Jobs\ChangeOrderStatus;
use Modules\Transaction\Models\Transaction;
use Modules\Transaction\Services\WorkingDayService;
use Z1lab\JsonApi\Repositories\ApiRepository;

class TransactionRepository extends ApiRepository
{
    /**
     * @var \Modules\Transaction\Services\WorkingDayService
     */
    private $service;

    /**
     * TransactionRepository constructor.
     *
     * @param  \Modules\Transaction\Models\Transaction  $model
     * @param  \Modules\Transaction\Services\WorkingDayService  $service
     */
    public function __construct(Transaction $model, WorkingDayService $service)
    {
        parent::__construct($model, 'transaction');
        $this->service = $service;
    }

    /**
     * @param  array  $data
     *
     * @return \Modules\Transaction\Models\Transaction
     */
    public function create(array $data)
    {
        /** @var \Modules\Transaction\Models\Transaction $transaction */
        $transaction = $this->model->create(array_only($data, ['amount', 'hash', 'ip', 'order_id']));

        $this->createCustomer($transaction, $data['customer'], $data['type'] === 'boleto');

        $transaction->items()->createMany($data['items']);

        $method = $transaction->payment_method()->create(['type' => $data['type']]);
        if ($data['type'] === 'boleto') {
            $description = $data['description'];
            $due_date = $this->service->nextWorkingDay(today());
            $method->boleto()->create(compact('due_date', 'description'));
        } else {
            $this->createCard($transaction, $data['card']);
        }

        $transaction->save();

        $this->setCacheKey($transaction->id);
        $this->remember($transaction);

        return $transaction;
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $original
     *
     * @return \Modules\Transaction\Models\Transaction
     */
    private function createReversed(Transaction $original)
    {
        /** @var \Modules\Transaction\Models\Transaction $transaction */
        $transaction = $this->model->create([
            'amount'     => $original->amount,
            'net_amount' => $original->net_amount,
            'hash'       => $original->hash,
            'ip'         => $original->ip,
            'order_id'   => $original->order_id,
            'status'     => 'reversed',
            'paid_at'    => now(),
            'code'       => $original->code,
        ]);

        $this->createCustomer($transaction, $original->customer->toArray(), $original->payment_method->type === 'boleto');

        foreach ($original->items as $item) {
            $transaction->items()->create($item->toArray());
        }

        $method = $transaction->payment_method()->create(['type' => $original->payment_method->type]);
        if ($method->type === 'boleto') {
            $method->boleto()->create([
                'url'      => $original->payment_method->boleto->url,
                'due_date' => $original->payment_method->boleto->due_date,
                'barcode'  => $original->payment_method->boleto->barcode,
            ]);
        } else {
            $this->createCard($transaction, $original->payment_method->card->toArray());
        }

        $transaction->save();

        $this->setCacheKey($transaction->id);
        $this->remember($transaction);

        return $transaction;
    }

    /**
     * @param  string  $code
     *
     * @return \Modules\Transaction\Models\Transaction
     */
    public function getByCode(string $code)
    {
        $transaction = $this->model->where('code', $code)->oldest()->first();

        if (NULL === $transaction) {
            abort(404);
        }

        return $transaction;
    }

    /**
     * @param  string  $code
     * @param  float  $netAmount
     *
     * @return bool
     */
    public function setNetAmount(string $code, float $netAmount)
    {
        $transaction = $this->getByCode($code);

        return $transaction->update(['net_amount' => intval($netAmount * 100)]);
    }

    /**
     * @param  string  $code
     * @param  string  $date
     *
     * @return bool
     */
    public function markAsPaid(string $code, string $date)
    {
        $transaction = $this->getByCode($code);

        $return = $transaction->update([
            'status'  => 'paid',
            'paid_at' => Carbon::createFromTimeString($date),
        ]);

        ChangeOrderStatus::dispatch($transaction);

        return $return;
    }

    /**
     * @param  string  $code
     *
     * @return bool
     */
    public function makeChargeback(string $code)
    {
        $original = $this->getByCode($code);

        if ($original->status !== 'paid') {
            abort(400, 'Transaction was not paid.');
        }

        if ($this->model->where([['code' => $original->code], ['status', 'reversed']])->exists()) {
            return TRUE;
        }

        $reversed = $this->createReversed($original);

        ChangeOrderStatus::dispatch($reversed);

        return TRUE;
    }

    /**
     * @param  string  $code
     * @param  float  $netAmount
     *
     * @return bool
     */
    public function cancel(string $code, float $netAmount = NULL)
    {
        $transaction = $this->getByCode($code);

        if (filled($netAmount)) {
            $return = $transaction->update([
                'status'     => 'canceled',
                'net_amount' => intval($netAmount * 100),
            ]);
        } else {
            $return = $transaction->update(['status' => 'canceled']);
        }

        ChangeOrderStatus::dispatch($transaction);

        return $return;
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     * @param  string  $code
     *
     * @return bool
     */
    public function setCode(Transaction &$transaction, string $code)
    {
        return $transaction->update(['code' => $code]);
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     * @param  string  $url
     * @param  string  $barcode
     *
     * @return bool
     */
    public function updateBoleto(Transaction &$transaction, string $url, string $barcode)
    {
        $transaction->payment_method->boleto->update(compact('url', 'barcode'));
        $transaction->payment_method->save();

        return $transaction->save();

    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiredBoletos()
    {
        return $this->model->where('payment_method.type', 'boleto')
            ->where('status', 'waiting')
            ->where('payment_method.boleto.due_date', '<=', $this->service->previousWorkingDay(today()))
            ->get();
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     * @param  array  $data
     * @param  bool  $is_boleto
     */
    private function createCustomer(Transaction &$transaction, array $data, bool $is_boleto)
    {
        $customer = $transaction->customer()->create(array_except($data, ['phone', 'address']));
        if ($is_boleto) {
            $customer->address()->create($data['address']);
        }
        $customer->phone()->create($data['phone']);
        $customer->save();
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     * @param  array  $data
     */
    private function createCard(Transaction &$transaction, array $data)
    {
        $card = $transaction->payment_method->card()->create(array_except($data, ['holder']));
        $holder = $card->holder()->create(array_except($data['holder'], ['address', 'phone']));
        $holder->phone()->create($data['holder']['phone']);
        $holder->address()->create($data['holder']['address']);
        $holder->save();
        $card->save();
    }
}
