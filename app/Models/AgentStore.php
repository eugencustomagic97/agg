<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AgentStore extends Pivot
{
    protected $table = 'agent_store';
    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function agent(){
        return $this->hasOne('App\Models\User', 'id', 'agent_id');
    }

    public function store(){
        return $this->hasOne('App\Models\Store', 'id', 'store_id');
    }
}
