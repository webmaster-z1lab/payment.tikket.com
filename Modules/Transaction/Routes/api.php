<?php

Route::middleware('api.v:1,transaction')->prefix('v1')->group(static function ()
{
    Route::apiResource('transactions', 'TransactionController')->only(['show', 'store', 'destroy']);
});
