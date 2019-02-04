<?php

namespace Modules\Transaction\Http\Resources\v1;

use Illuminate\Http\Resources\Json\Resource;

class Card extends Resource
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
            'id'           => $this->id,
            'brand'        => $this->brand,
            'number'       => $this->number,
            'token'        => $this->token,
            'installments' => $this->installments,
            'parcel'       => $this->parcel,
            'holder'       => api_resource('Holder')->make($this->holder),
        ];
    }
}
