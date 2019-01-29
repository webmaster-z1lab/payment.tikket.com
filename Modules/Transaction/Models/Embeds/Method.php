<?php

namespace Modules\Transaction\Models\Embeds;

use Jenssegers\Mongodb\Eloquent\Model;

class Method extends Model
{
    public $timestamps = FALSE;

    protected $fillable = ['type'];

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsOne
     */
    public function card()
    {
        return $this->embedsOne(Card::class);
    }

    /**
     * @return \Jenssegers\Mongodb\Relations\EmbedsOne
     */
    public function boleto()
    {
        return $this->embedsOne(Boleto::class);
    }
}
