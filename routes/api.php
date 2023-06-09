<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/auth/login', '\App\Http\Controllers\Api\AuthController@login');
Route::get('/unauthorized', '\App\Http\Controllers\Api\AuthController@unauthorized');

Route::middleware('auth:api')->get('/ping', '\App\Http\Controllers\Api\AuthController@ping');
Route::middleware('auth:api')->get('/pre-sync', '\App\Http\Controllers\Api\AuthController@preSync');

Route::get('/clients/sync', '\App\Http\Controllers\Api\ClientController@sync');
Route::middleware('auth:api')->get('/clients/list', '\App\Http\Controllers\Api\ClientController@list');
Route::middleware('auth:api')->get('/client/{client}/stores-list', '\App\Http\Controllers\Api\ClientController@storesList');
Route::middleware('auth:api')->get('/client/{client}/contacts-list', '\App\Http\Controllers\Api\ClientController@contactsList');

Route::get('/agents/sync', '\App\Http\Controllers\Api\AgentController@sync');

Route::get('/nomenclature/sync', '\App\Http\Controllers\Api\NomenclatureController@sync');
Route::get('/balance/sync', '\App\Http\Controllers\Api\NomenclatureController@syncBalance');
Route::middleware('auth:api')->get('/nomenclature/list', '\App\Http\Controllers\Api\NomenclatureController@list');
Route::middleware('auth:api')->get('/balance/list', '\App\Http\Controllers\Api\NomenclatureController@balance');
Route::middleware('auth:api')->get('/nomenclature/categories-list', '\App\Http\Controllers\Api\NomenclatureController@categoriesList');
Route::middleware('auth:api')->get('/nomenclature/suppliers-list', '\App\Http\Controllers\Api\NomenclatureController@suppliersList');

Route::middleware('auth:api')->get('/orders/list', '\App\Http\Controllers\Api\OrderController@list');
Route::middleware('auth:api')->post('/order/new', '\App\Http\Controllers\Api\OrderController@new');

Route::get('/returns/sync-types', '\App\Http\Controllers\Api\ReturnController@syncTypes');
Route::middleware('auth:api')->get('/returns/list', '\App\Http\Controllers\Api\ReturnController@list');
Route::middleware('auth:api')->get('/returns/types/list', '\App\Http\Controllers\Api\ReturnController@listTypes');
Route::middleware('auth:api')->post('/returns/new', '\App\Http\Controllers\Api\ReturnController@new');

Route::middleware('auth:api')->get('/cash-order/list', '\App\Http\Controllers\Api\CashOrderController@list');
Route::middleware('auth:api')->post('/cash-order/new', '\App\Http\Controllers\Api\CashOrderController@new');

Route::post('/log/error', '\App\Http\Controllers\Api\LogController@error');