<?php

namespace Modules\Transaction\Http\Controllers;

use Modules\Transaction\Http\Requests\TransactionRequest;
use Modules\Transaction\Repositories\TransactionRepository;
use Modules\Transaction\Services\TransactionService;
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
     * @param  \Modules\Transaction\Repositories\TransactionRepository  $repository
     * @param  \Modules\Transaction\Services\TransactionService  $service
     */
    public function __construct(TransactionRepository $repository, TransactionService $service)
    {
        parent::__construct($repository, 'Transaction');
        $this->service = $service;

        $this->middleware('auth.m2m');
    }

    /**
     * @param  \Modules\Transaction\Http\Requests\TransactionRequest  $request
     *
     * @return \Illuminate\Http\Resources\Json\Resource
     * @throws \Exception
     */
    public function store(TransactionRequest $request)
    {
        $transaction = $this->repository->create($request->validated());

        $transaction = $this->service->create($transaction);

        return $this->makeResource($transaction->fresh());
    }

    /**
     * @param  string  $id
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
        } else {
            abort(400, "This transaction can't be canceled.");
        }

        return $this->makeResource($transaction->fresh());
    }
}
