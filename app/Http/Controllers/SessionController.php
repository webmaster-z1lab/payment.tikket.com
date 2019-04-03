<?php

namespace App\Http\Controllers;

use PagSeguro\Configuration\Configure;
use PagSeguro\Services\Session;

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
     * @throws \Exception
     */
    public function get()
    {
        $response = Session::create(Configure::getAccountCredentials());

        return response()->json(['session' => $response->getResult()]);
    }
}
