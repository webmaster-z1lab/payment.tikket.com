<?php

namespace App\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use PagSeguro\Configuration\Configure;
use PagSeguro\Services\Session;
use Z1lab\JsonApi\Exceptions\ErrorObject;

class SessionController extends Controller
{
    /**
     * SessionController constructor.
     */
    public function __construct()
    {
//        $this->middleware('auth');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        try {
            $response = Session::create(Configure::getAccountCredentials());
        } catch (\Exception $e) {
            $error = new ErrorObject($e->getMessage(), $e->getCode());

            throw new HttpResponseException(response()->json($error->toArray(), $error->getCode()));
        }

        return response()->json(['session' => $response->getResult()]);
    }
}
