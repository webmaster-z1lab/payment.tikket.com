<?php

namespace Modules\Transaction\Http\Resources\v1;

use Illuminate\Http\Resources\Json\Resource;

class Costumer extends Resource
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
            'id'       => $this->id,
            'user_id'  => $this->user_id,
            'name'     => $this->name,
            'email'    => $this->email,
            'document' => substr($this->document, 0, 3) . '.***.***-**',
            'phone'    => api_resource('Phone')->make($this->phone),
        ];
    }
}
