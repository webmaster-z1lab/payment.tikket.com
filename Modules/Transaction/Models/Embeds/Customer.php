<?php

namespace Modules\Transaction\Models\Embeds;

use Jenssegers\Mongodb\Eloquent\Model;

class Customer extends Model
{
    public $timestamps = FALSE;

    protected $fillable = ['user_id', 'name', 'email', 'document'];

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsOne
     */
    public function phone()
    {
        return $this->embedsOne(Phone::class);
    }

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsOne
     */
    public function address()
    {
        return $this->embedsOne(Address::class);
    }
}
