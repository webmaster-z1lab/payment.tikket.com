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
use Z1lab\JsonApi\Repositories\ApiRepository;

class TransactionRepository extends ApiRepository
{
    /**
     * TransactionRepository constructor.
     *
     * @param \Modules\Transaction\Models\Transaction $model
     */
    public function __construct(Transaction $model)
    {
        parent::__construct($model, 'transaction');
    }

    /**
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model|mixed|\Z1lab\JsonApi\Repositories\ApiRepository
     */
    public function create(array $data)
    {
        /** @var \Modules\Transaction\Models\Transaction $transaction */
        $transaction = $this->model->create(array_only($data, ['amount', 'hash', 'ip', 'order_id']));

        $this->createCostumer($transaction, $data['costumer']);

        $transaction->items()->createMany($data['items']);

        $method = $transaction->payment_method()->create(['type' => $data['type']]);
        if ($data['type'] === 'boleto')
            $method->boleto()->create();
        else
            $this->createCard($transaction, $data['card']);

        $transaction->save();

        $this->setCacheKey($transaction->id);
        $this->remember($transaction);

        return $transaction;
    }

    /**
     * @param string $code
     *
     * @return \Modules\Transaction\Models\Transaction
     */
    public function getByCode(string $code)
    {
        $transaction = $this->model->where('code', $code)->oldest()->first();

        if (NULL === $transaction) abort(404);

        return $transaction;
    }

    /**
     * @param string $code
     * @param float  $netAmount
     *
     * @return bool
     */
    public function setNetAmount(string $code, float $netAmount)
    {
        $transaction = $this->getByCode($code);

        return $transaction->update(['net_amount' => intval($netAmount * 100)]);
    }

    /**
     * @param string $code
     * @param string $date
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
     * @param string $code
     *
     * @return bool
     */
    public function makeChargeback(string $code)
    {
        $original = $this->getByCode($code)->toArray();

        if ($original === NULL || empty($original))
            abort(404);

        if ($original['status'] !== 'paid')
            abort(404, 'Transaction was not paid.');

        if ($this->model->where([['code' => $original['code']], ['status', 'reversed']])->exists())
            return TRUE;

        $original['type'] = $original['payment_method']['type'];

        $reversed = $this->create($original);
        $reversed->update([
            'status'  => 'reversed',
            'paid_at' => now(),
        ]);

        ChangeOrderStatus::dispatch($reversed);

        if ($original['payment_method']['type'] === 'boleto') {
            $method = $reversed->payment_method;
            $method->boleto->update($original['payment_method']['boleto']);
            $method->save();
        }

        return $reversed->save();
    }

    /**
     * @param string $code
     * @param float  $netAmount
     *
     * @return bool
     */
    public function cancel(string $code, float $netAmount = NULL)
    {
        $transaction = $this->getByCode($code);

        if (filled($netAmount))
            $return = $transaction->update([
                'status'     => 'canceled',
                'net_amount' => intval($netAmount * 100),
            ]);
        else
            $return = $transaction->update(['status' => 'canceled']);

        ChangeOrderStatus::dispatch($transaction);

        return $return;
    }

    /**
     * @param \Modules\Transaction\Models\Transaction $transaction
     * @param string                                  $code
     *
     * @return bool
     */
    public function setCode(Transaction &$transaction, string $code)
    {
        return $transaction->update(['code' => $code]);
    }

    /**
     * @param \Modules\Transaction\Models\Transaction $transaction
     * @param string                                  $url
     *
     * @return bool
     */
    public function updateBoleto(Transaction &$transaction, string $url)
    {
        $transaction->payment_method->boleto->update(['url' => $url]);
        $transaction->payment_method->save();

        return $transaction->save();

    }

    /**
     * @param \Modules\Transaction\Models\Transaction $transaction
     * @param array                                   $data
     */
    private function createCostumer(Transaction &$transaction, array $data)
    {
        $costumer = $transaction->costumer()->create(array_except($data, ['phone']));
        $costumer->phone()->create($data['phone']);
        $costumer->save();
    }

    /**
     * @param \Modules\Transaction\Models\Transaction $transaction
     * @param array                                   $data
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
