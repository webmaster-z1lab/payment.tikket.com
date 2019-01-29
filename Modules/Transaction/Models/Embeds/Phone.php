<?php

namespace Modules\Transaction\Models\Embeds;

use Jenssegers\Mongodb\Eloquent\Model;

class Phone extends Model
{
    public $timestamps = FALSE;

    protected $fillable = ['area_code', 'phone'];
}
