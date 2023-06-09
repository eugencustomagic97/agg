<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
   
    Route::crud('client', 'ClientCrudController');
    Route::crud('store', 'StoreCrudController');
    Route::crud('contact', 'ContactCrudController');
    Route::crud('category', 'CategoryCrudController');
    Route::crud('supplier', 'SupplierCrudController');
    Route::crud('nomenclature', 'NomenclatureCrudController');
    Route::crud('territorial-division', 'TerritorialDivisionCrudController');
    Route::crud('balance', 'BalanceCrudController');
    Route::crud('status', 'StatusCrudController');
    Route::crud('order', 'OrderCrudController');
    Route::crud('return-type', 'ReturnTypeCrudController');
    Route::crud('returns', 'ReturnsCrudController');
    Route::crud('cash-order', 'CashOrderCrudController');
    Route::crud('client-balance', 'ClientBalanceCrudController');
    Route::crud('client-hystory', 'ClientHistoryCrudController');
	Route::crud('synchronization', 'SynchronizationCrudController');
    Route::crud('price-type', 'PriceTypeCrudController');
    Route::crud('error-log', 'ErrorLogCrudController');
}); // this should be the absolute last line of this file