<?php

Route::middleware('api.v:1,transaction')->prefix('v1')->group(function ()
{
    Route::apiResource('transactions', 'TransactionController');
});
