<?php

namespace Modules\Transaction\Http\Resources\v1;

use Illuminate\Http\Resources\Json\Resource;

class Item extends Resource
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
            'id'          => $this->id,
            'item_id'     => $this->item_id,
            'description' => $this->description,
            'amount'      => $this->amount,
            'quantity'    => $this->quantity,
        ];
    }
}
