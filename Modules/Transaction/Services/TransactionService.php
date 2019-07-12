<?php
/**
 * Created by PhpStorm.
 * User: Felipe
 * Date: 24/01/2019
 * Time: 17:21
 */

namespace Modules\Transaction\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Modules\Transaction\Models\Transaction;
use Modules\Transaction\Repositories\TransactionRepository;
use PagSeguro\Configuration\Configure;
use PagSeguro\Domains\Requests\DirectPayment\CreditCard;
use PagSeguro\Services\Transactions\Cancel;
use PagSeguro\Services\Transactions\Refund;

class TransactionService
{
    /**
     * @var \Modules\Transaction\Repositories\TransactionRepository
     */
    private $repository;

    protected $notication_url;

    /**
     * TransactionService constructor.
     *
     * @param  \Modules\Transaction\Repositories\TransactionRepository  $repository
     */
    public function __construct(TransactionRepository $repository)
    {
        $this->repository = $repository;
        //$this->notication_url = Str::finish(config('app.url'), '/').'api/notifications';
        $this->notication_url = 'https://tikket.com.br/api/notifications';
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     *
     * @return \Modules\Transaction\Models\Transaction|null
     * @throws \Exception
     */
    public function create(Transaction $transaction)
    {
        switch ($transaction->payment_method->type) {
            case 'boleto':
                return $this->createBoleto($transaction);
            case 'credit_card':
                return $this->createCreditCard($transaction);
            default:
                abort(400, 'Payment method unknown.');

                return NULL;
        }
    }

    /**
     * @param  string  $code
     *
     * @return \PagSeguro\Parsers\Cancel\Response
     * @throws \Exception
     */
    public function cancel(string $code)
    {
        return Cancel::create(Configure::getAccountCredentials(), $code);
    }

    /**
     * @param  string  $code
     *
     * @return string
     * @throws \Exception
     */
    public function reverse(string $code)
    {
        return Refund::create(Configure::getAccountCredentials(), $code);
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     *
     * @return \Modules\Transaction\Models\Transaction
     */
    private function createBoleto(Transaction $transaction)
    {
        $url = 'https://ws.pagseguro.uol.com.br/';

        $client = new Client([
            'base_uri' => $url,
            'headers'  => ['Content-Type' => 'application/json;charset=ISO-8859-1', 'Accept' => 'application/json;charset=ISO-8859-1'],
            'query'    => ['email' => Configure::getAccountCredentials()->getEmail(), 'token' => Configure::getAccountCredentials()->getToken()],
        ]);

        $response = $client->post('recurring-payment/boletos',
            [
                'json' => [
                    'reference'        => $transaction->id,
                    'firstDueDate'     => $transaction->payment_method->boleto->due_date->format('Y-m-d'),
                    'numberOfPayments' => 1,
                    'periodicity'      => 'monthly',
                    'instructions'     => $transaction->payment_method->boleto->description,
                    'description'      => $transaction->payment_method->boleto->description,
                    'amount'           => number_format($transaction->amount / 100.0, 2),
                    'customer'         => [
                        'document' => [
                            'type'  => 'CPF',
                            'value' => $transaction->customer->document,
                        ],
                        'name'     => $transaction->customer->name,
                        'email'    => $transaction->customer->email,
                        'phone'    => [
                            'areaCode' => $transaction->customer->phone->area_code,
                            'number'   => $transaction->customer->phone->phone,
                        ],
                        'address'  => [
                            'postalCode' => $transaction->customer->address->postal_code,
                            'street'     => $transaction->customer->address->street,
                            'number'     => $transaction->customer->address->number,
                            'complement' => $transaction->customer->address->complement,
                            'district'   => $transaction->customer->address->district,
                            'city'       => $transaction->customer->address->city,
                            'state'      => $transaction->customer->address->state,
                        ],
                    ],
                    'notificationURL'  => $this->notication_url,
                ],
            ]);

        $result = json_decode((string) $response->getBody(), TRUE);

        if (!$this->repository->setCode($transaction, $result['boletos'][0]['code'])) {
            \Log::error('Not possible to set the code in the transaction.['.$transaction->id.' => '.$result['boletos'][0]['code'].']');
        }

        if (!$this->repository->updateBoleto($transaction, $result['boletos'][0]['paymentLink'], $result['boletos'][0]['barcode'])) {
            \Log::error('Not possible to set the boleto in the transaction.['.$transaction->id.' => '.$result['boletos'][0]['paymentLink'].','.
                        $result['boletos'][0]['barcode'].']');
        }

        return $transaction;
    }

    /**
     * @param  \Modules\Transaction\Models\Transaction  $transaction
     *
     * @return \Modules\Transaction\Models\Transaction
     * @throws \Exception
     */
    private function createCreditCard(Transaction $transaction)
    {
        $request = new CreditCard();

        $request->setMode('default');

        $request->setCurrency('BRL');

        $request->setReference($transaction->id);

        $request->setReceiverEmail(config('pagseguro.email'));

        $request->setShipping()->setAddressRequired()->withParameters(FALSE);

        foreach ($transaction->items as $item) {
            $request->addItems()->withParameters(
                $item->item_id,
                $item->description,
                $item->quantity,
                $item->amount / 100.0
            );
        }

        $request->setExtraAmount(number_format(0, 2));

        $request->setSender()->setName($transaction->customer->name)
            ->setEmail($transaction->customer->email);
        $request->setSender()->setIp($transaction->ip);
        $request->setSender()->setHash($transaction->hash);

        $request->setSender()->setDocument()->withParameters('CPF', $transaction->customer->document);
        $request->setSender()->setPhone()->withParameters($transaction->customer->phone->area_code, $transaction->customer->phone->phone);

        $request->setBilling()->setAddress()->withParameters(
            $transaction->payment_method->card->holder->address->street,
            $transaction->payment_method->card->holder->address->number,
            $transaction->payment_method->card->holder->address->district,
            $transaction->payment_method->card->holder->address->postal_code,
            $transaction->payment_method->card->holder->address->city,
            $transaction->payment_method->card->holder->address->state,
            'BRA',
            $transaction->payment_method->card->holder->address->complement
        );

        $request->setHolder()->setName($transaction->payment_method->card->holder->name);
        $request->setHolder()->setBirthDate($transaction->payment_method->card->holder->birth_date->format('d/m/Y'));
        $request->setHolder()->setDocument()->withParameters('CPF', $transaction->payment_method->card->holder->document);
        $request->setHolder()->setPhone()->withParameters($transaction->payment_method->card->holder->phone->area_code, $transaction->payment_method->card->holder->phone->phone);

        $request->setInstallment()->withParameters($transaction->payment_method->card->installments,
            number_format($transaction->payment_method->card->parcel / 100.0, 2));

        $request->setToken($transaction->payment_method->card->token);

        $request->setNotificationUrl($this->notication_url);

        $response = $request->register(Configure::getAccountCredentials());

        if (!$this->repository->setCode($transaction, $response->getCode())) {
            \Log::error('Not possible to set the code in the transaction.['.$transaction->id.' => '.$response->getCode().']');
        }

        return $transaction;
    }
}
