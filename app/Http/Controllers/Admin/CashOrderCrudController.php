<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CashOrderRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class CashOrderCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CashOrderCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\CashOrder::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cash-order');
        CRUD::setEntityNameStrings('cash order', 'cash orders');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        //CRUD::column('id');
        CRUD::column('guid');
        CRUD::addColumn([
            'name' => 'agent_id',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $ids = \App\Models\User::select('id')->where('name', 'like', '%'.$searchTerm.'%')->get()->toArray();
                $query->orWhereIn('agent_id',$ids);
            }
        ]);
        //CRUD::column('agent_guid');
        CRUD::addColumn([
            'name' => 'client_id',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $ids = \App\Models\Client::select('id')->where('name', 'like', '%'.$searchTerm.'%')->get()->toArray();
                $query->orWhereIn('client_id',$ids);
            }
        ]);
        //CRUD::column('client_guid');
        //CRUD::column('order_id');
        CRUD::column('order_guid');
        CRUD::column('organization_id');
        //CRUD::column('organization_guid');
        CRUD::column('store_id')->limit(100);
        //CRUD::column('store_guid');
        CRUD::column('amount');
        CRUD::column('comment');
        CRUD::column('created_at');
        CRUD::column('updated_at');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CashOrderRequest::class);

        CRUD::field('id');
        CRUD::field('guid');
        CRUD::field('agent_id');
        CRUD::field('agent_guid');
        CRUD::field('client_id');
        CRUD::field('client_guid');
        CRUD::field('order_id');
        CRUD::field('order_guid');
        CRUD::field('organization_id');
        CRUD::field('organization_guid');
        CRUD::field('store_id');
        CRUD::field('store_guid');
        CRUD::field('amount');
        CRUD::field('comment');
        CRUD::field('created_at');
        CRUD::field('updated_at');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
