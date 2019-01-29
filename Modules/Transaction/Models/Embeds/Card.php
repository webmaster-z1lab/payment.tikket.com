<?php

namespace Modules\Transaction\Models\Embeds;

use Jenssegers\Mongodb\Eloquent\Model;

class Card extends Model
{
    public $timestamps = FALSE;

    protected $fillable = ['brand', 'number', 'token', 'installments', 'parcel'];

    protected $casts = [
        'installments' => 'integer',
        'parcel'       => 'integer',
    ];

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsOne
     */
    public function holder()
    {
        return $this->embedsOne(Holder::class);
    }

    /**
     * @param $value
     */
    public function setNumberAttribute($value)
    {
        $this->attributes['number'] = str_pad($value, 16, '*', STR_PAD_LEFT);
    }
}
