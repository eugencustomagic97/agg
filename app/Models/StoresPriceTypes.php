<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StoresPriceTypes extends Pivot
{
    protected $table = 'stores_price_types';
    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function store(){
        return $this->hasOne('App\Models\Store', 'id', 'store_id');
    }

    public function priceType(){
        return $this->hasOne('App\Models\PriceType', 'id', 'price_type_id');
    }
}
