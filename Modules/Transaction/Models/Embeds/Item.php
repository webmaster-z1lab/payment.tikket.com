<?php

namespace Modules\Transaction\Models\Embeds;

use Jenssegers\Mongodb\Eloquent\Model;

class Item extends Model
{
    public $timestamps = FALSE;

    protected $fillable = ['item_id', 'description', 'amount', 'quantity'];

    protected $casts = [
        'amount'   => 'integer',
        'quantity' => 'integer',
    ];
}
