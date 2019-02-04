<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Transaction\Http\Requests\TransactionRequest;
use Modules\Transaction\Repositories\TransactionRepository;
use Modules\Transaction\Services\TransactionService;
use PagSeguro\Configuration\Configure;
use Z1lab\JsonApi\Exceptions\ErrorObject;
use Z1lab\JsonApi\Http\Controllers\ApiController;

class TransactionController extends ApiController
{
    /**
     * @var \Modules\Transaction\Services\TransactionService
     */
    private $service;

    public function __construct(TransactionRepository $repository, TransactionService $service)
    {
        parent::__construct($repository, 'Transaction');
        $this->service = $service;
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

        try {
            $response = $request->register(Configure::getAccountCredentials());
        } catch (\Exception $e) {
            $error = new ErrorObject($e->getMessage(), $e->getCode(), $e->getTrace());

            throw new HttpResponseException(response()->json($error->toArray(), $error->getCode()));
        }

        try {
            if (!$this->repository->setCode($transaction, $response->getCode()))
                throw new \Exception('Not possible to set the code in the transaction.[' . $transaction->id . ' => ' . $response->getCode() . ']');

            if ($transaction->payment_method->type === 'boleto' && !$this->repository->updateBoleto($transaction, $response->paymentLink))
                throw new \Exception('Not possible to set the boleto in the transaction.[' . $transaction->id . ' => ' . $response->paymentLink . ']');
        } catch (\Exception $e) {
            $error = new ErrorObject($e->getMessage(), $e->getCode());

            throw new HttpResponseException(response()->json($error->toArray(), $error->getCode()));
        }

        return $this->makeResource($transaction);
    }
}
