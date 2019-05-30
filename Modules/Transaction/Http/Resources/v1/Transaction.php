<?php

namespace Modules\Transaction\Http\Resources\v1;

use Illuminate\Http\Resources\Json\Resource;

class Transaction extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'types'      => 'transactions',
            'attributes' => [
                'status'         => $this->status,
                'code'           => $this->code,
                'amount'         => $this->amount,
                'net_amount'     => $this->net_amount,
                'paid_at'        => optional($this->paid_at)->toW3cString(),
                'hash'           => $this->hash,
                'ip'             => $this->ip,
                'customer'       => api_resource('Customer')->make($this->customer),
                'items'          => api_resource('Item')->collection($this->items),
                'payment_method' => api_resource('Method')->make($this->payment_method),
            ],
        ];
    }
}
