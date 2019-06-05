<?php

namespace Modules\Transaction\Http\Resources\v1;

use Illuminate\Http\Resources\Json\Resource;


class Boleto extends Resource
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
            'url'         => $this->url,
            'due_date'    => $this->due_date->toW3cString(),
            'barcode'     => $this->barcode,
            'description' => $this->description,
        ];
    }
}
