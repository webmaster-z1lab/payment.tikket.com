<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use PagSeguro\Configuration\Configure;
use PagSeguro\Enum\Notification;
use PagSeguro\Helpers\Xhr;
use PagSeguro\Services\Transactions\Notification as TransactionNotification;
use Z1lab\JsonApi\Exceptions\ErrorObject;

class NotificationController extends Controller
{
    /**
     * @var \Modules\Transaction\Repositories\TransactionRepository
     */
    protected $repository;

    /**
     * NotificationController constructor.
     *
     * @param \Modules\Transaction\Repositories\TransactionRepository $repository
     */
    public function __construct(\Modules\Transaction\Repositories\TransactionRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            if (!Xhr::hasPost())
                throw new \Exception('Notification not found.');

            switch (Xhr::getInputType()) {
                case Notification::PRE_APPROVAL:
                    throw new \Exception('Notification type not suported.');
                    break;
                case Notification::TRANSACTION:
                    $this->transaction();
                    break;
                default:
                    throw new \Exception('Notification type unknown.');
            }
        } catch (\Exception $e) {
            $error = new ErrorObject($e->getMessage(), $e->getCode());

            throw new HttpResponseException(response()->json($error->toArray(), $error->getCode()));
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    protected function transaction()
    {
        try {
            $response = TransactionNotification::check(Configure::getAccountCredentials());

            if ($this->repository->getByCode($response->getCode()) === NULL)
                throw new \Exception('Transaction not found', Response::HTTP_NOT_FOUND);

            switch ($response->getStatus()) {
                case 1:
                case 2:
                    if ($this->repository->setNetAmount($response->getCode(), $response->getNetAmount()))
                        throw new \Exception('Not possible to set the net amount.');
                    break;
                case 4:
                case 5:
                case 8:
                case 9:
                    break;
                case 3:
                    if (!$this->repository->markAsPaid($response->getCode(), $response->getLastEventDate()))
                        throw new \Exception('Not possible to mark as paid.');
                    break;
                case 6:
                    if (!$this->repository->makeChargeback($response->getCode()))
                        throw new \Exception('Not possible to make the chargeback.');
                    break;
                case 7:
                    if (!$this->repository->cancel($response->getCode(), $response->getNetAmount()))
                        throw new \Exception('Not possible to cancel.');
                    break;
                default:
                    throw new \Exception('Unknown status.');
            }
        } catch (\Exception $e) {
            $error = new ErrorObject($e->getMessage(), $e->getCode());

            throw new HttpResponseException(response()->json($error->toArray(), $error->getCode()));
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
