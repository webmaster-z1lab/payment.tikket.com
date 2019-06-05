<?php

namespace Modules\Transaction\Models\Embeds;

use Jenssegers\Mongodb\Eloquent\Model;

class Boleto extends Model
{
    public $timestamps = FALSE;

    protected $fillable = ['url', 'due_date', 'barcode', 'description'];

    protected $dates = ['due_date'];
}
