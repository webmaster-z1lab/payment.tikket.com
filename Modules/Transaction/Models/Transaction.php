<?php

namespace Modules\Transaction\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Modules\Transaction\Models\Embeds\Customer;
use Modules\Transaction\Models\Embeds\Item;
use Modules\Transaction\Models\Embeds\Method;

class Transaction extends Model
{
    protected $fillable = ['status', 'code', 'order_id', 'amount', 'net_amount', 'paid_at', 'hash', 'ip'];

    protected $casts = [
        'amount'     => 'integer',
        'net_amount' => 'integer',
    ];

    protected $dates = ['paid_at'];

    protected $attributes = [
        'status'  => 'waiting',
        'paid_at' => NULL,
    ];

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsOne
     */
    public function customer()
    {
        return $this->embedsOne(Customer::class);
    }

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsMany
     */
    public function items()
    {
        return $this->embedsMany(Item::class);
    }

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsOne
     */
    public function payment_method()
    {
        return $this->embedsOne(Method::class);
    }
}
