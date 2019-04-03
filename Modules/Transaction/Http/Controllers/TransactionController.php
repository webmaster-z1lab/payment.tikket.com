<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Transaction\Http\Requests\TransactionRequest;
use Modules\Transaction\Repositories\TransactionRepository;
use Modules\Transaction\Services\TransactionService;
use PagSeguro\Configuration\Configure;
use Z1lab\JsonApi\Exceptions\ErrorObject;
use Z1lab\JsonApi\Http\Controllers\ApiController;

/**
 * Class TransactionController
 *
 * @package Modules\Transaction\Http\Controllers
 *
 * @property \Modules\Transaction\Repositories\TransactionRepository repository
 */
class TransactionController extends ApiController
{
    /**
     * @var \Modules\Transaction\Services\TransactionService
     */
    private $service;

    /**
     * TransactionController constructor.
     *
     * @param \Modules\Transaction\Repositories\TransactionRepository $repository
     * @param \Modules\Transaction\Services\TransactionService        $service
     */
    public function __construct(TransactionRepository $repository, TransactionService $service)
    {
        parent::__construct($repository, 'Transaction');
        $this->service = $service;

        $this->middleware('auth.m2m');
    }

    /**
     * @param \Modules\Transaction\Http\Requests\TransactionRequest $request
     *
     * @return \Illuminate\Http\Resources\Json\Resource
     * @throws \Exception
     */
    public function store(TransactionRequest $request)
    {
        $transaction = $this->repository->create($request->validated());

        $request = $this->service->create($transaction);

        $response = $request->register(Configure::getAccountCredentials());

        if (!$this->repository->setCode($transaction, $response->getCode()))
           abort(400,'Not possible to set the code in the transaction.[' . $transaction->id . ' => ' . $response->getCode() . ']');

        if ($transaction->payment_method->type === 'boleto' && !$this->repository->updateBoleto($transaction, $response->getPaymentLink()))
            abort(400,'Not possible to set the boleto in the transaction.[' . $transaction->id . ' => ' . $response->getPaymentLink() . ']');

        return $this->makeResource($transaction);
    }

    /**
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\Resource
     * @throws \Exception
     */
    public function destroy(string $id)
    {
        $transaction = $this->repository->find($id);

        if ($transaction->status === 'waiting') {
            $this->service->cancel($transaction->code);

            $this->repository->cancel($transaction->code);
        } elseif ($transaction->status === 'paid') {
            $this->service->reverse($transaction->code);

            $this->repository->makeChargeback($transaction->code);
        } else
            abort(400, "This transaction can't be canceled.");

        return $this->makeResource($transaction->fresh());
    }
}
