<?php

namespace Modules\Transaction\Http\Resources\v1;

use Illuminate\Http\Resources\Json\Resource;

class Method extends Resource
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
            'id'   => $this->id,
            'type' => $this->type,
            'card' => $this->when(ends_with($this->type, 'card'), api_resource('Card')->make($this->card)),
        ];
    }
}
