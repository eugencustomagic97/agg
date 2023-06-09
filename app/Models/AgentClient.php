<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AgentClient extends Pivot
{
    protected $table = 'agent_client';
    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function agent(){
        return $this->hasOne('App\Models\User', 'id', 'agent_id');
    }

    public function client(){
        return $this->hasOne('App\Models\Client', 'id', 'client_id');
    }
}
