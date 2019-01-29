<?php
/**
 * Created by PhpStorm.
 * User: Felipe
 * Date: 24/01/2019
 * Time: 17:21
 */

namespace Modules\Transaction\Services;

use Modules\Transaction\Models\Transaction;;

use PagSeguro\Domains\Item;
use PagSeguro\Domains\Requests\DirectPayment\Boleto;
use PagSeguro\Domains\Requests\DirectPayment\CreditCard;

class TransactionService
{
    /**
     * @param \Modules\Transaction\Models\Transaction $transaction
     *
     * @return \PagSeguro\Domains\Requests\DirectPayment\Boleto|\PagSeguro\Domains\Requests\DirectPayment\CreditCard
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
                throw new \Exception('Payment method unknown.');
        }
    }

    /**
     * @param \Modules\Transaction\Models\Transaction $transaction
     *
     * @return \PagSeguro\Domains\Requests\DirectPayment\Boleto
     */
    private function createBoleto(Transaction $transaction)
    {
        $request = new Boleto();

        $request->setMode('default');

        $request->setCurrency('BRL');

        $request->setReference($transaction->id);

        $request->setReceiverEmail(config('pagseguro.email'));

        $request->setShipping()->setAddressRequired()->withParameters(FALSE);

        foreach ($transaction->items as $item)
            $request->addItems()->withParameters(
                $item->item_id,
                $item->description,
                $item->quantity,
                $item->amount / 100.0
            );

        $request->setExtraAmount(number_format(0, 2));

        $request->setSender()->setName($transaction->costumer->name)
            ->setEmail($transaction->costumer->email);
        $request->setSender()->setIp($transaction->ip);
        $request->setSender()->setHash($transaction->hash);

        $request->setSender()->setDocument()->withParameters('CPF', $transaction->costumer->document);
        $request->setSender()->setPhone()->withParameters($transaction->costumer->phone->area_code, $transaction->costumer->phone->phone);

        return $request;
    }

    /**
     * @param \Modules\Transaction\Models\Transaction $transaction
     *
     * @return \PagSeguro\Domains\Requests\DirectPayment\CreditCard
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

        $request->setSender()->setName($transaction->costumer->name)
            ->setEmail($transaction->costumer->email);
        $request->setSender()->setIp($transaction->ip);
        $request->setSender()->setHash($transaction->hash);

        $request->setSender()->setDocument()->withParameters('CPF', $transaction->costumer->document);
        $request->setSender()->setPhone()->withParameters($transaction->costumer->phone->area_code, $transaction->costumer->phone->phone);

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

        return $request;
    }
}