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
     * @throws \Exception
     */
    public function index()
    {

        if (!Xhr::hasPost())
            throw new \Exception('Notification not found.', 404);

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

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    protected function transaction()
    {
        $response = TransactionNotification::check(Configure::getAccountCredentials());

        switch ($response->getStatus()) {
            case 1:
            case 2:
                $this->repository->setNetAmount($response->getCode(), $response->getNetAmount());
                break;
            case 4:
            case 5:
            case 8:
            case 9:
                break;
            case 3:
                $this->repository->markAsPaid($response->getCode(), $response->getLastEventDate());
                break;
            case 6:
                $this->repository->makeChargeback($response->getCode());
                break;
            case 7:
                $this->repository->cancel($response->getCode(), $response->getNetAmount());
                break;
            default:
                throw new \Exception('Unknown status.');
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
