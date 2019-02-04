<?php

namespace Modules\Transaction\Http\Resources\v1;

use Illuminate\Http\Resources\Json\Resource;

class Holder extends Resource
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
            'name'       => $this->name,
            'document'   => substr($this->document, 0, 3) . '.***.***-**',
            'birth_date' => $this->birth_date->format('Y-m-d'),
            'phone'      => api_resource('Phone')->make($this->phone),
            'address'    => api_resource('Address')->make($this->address),
        ];
    }
}
