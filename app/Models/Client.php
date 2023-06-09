<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'clients';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*public function stores()
    {
        return $this->hasMany('App\Models\Store', 'client_id', 'id');
    }*/
	
	public function stores()
    {
        $availableStores = \App\Models\AgentStore::select('store_id')->where('agent_id', auth()->user()->id)->get()->toArray();
        return $this->hasMany('App\Models\Store', 'client_id', 'id')->with(['priceTypes'])->whereIn('stores.id', $availableStores);
    }

    public function contacts()
    {
        return $this->belongsToMany('App\Models\Contact', 'client_id', 'id');
    }

    public function statuses()
    {
        return $this->hasMany('App\Models\Status', 'client_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
