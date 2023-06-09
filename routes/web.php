<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function(){

    $ip = file_get_contents('https://api.ipify.org');

    dd($ip);
    $apiUrl = \Config::get('api.api_base_url');
    $apiUser = \Config::get('api.api_user');
    $apiPassword = \Config::get('api.api_password');

    $auth = base64_encode($apiUser . ":" . $apiPassword);
    try {}
    catch(Exception $e){
        dump($e->getCode(), $e->getMessage());
    }
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "Agents/GetAgents/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $auth);

    $data = curl_exec($ch);

    if ($data === false) {
        throw new Exception(curl_error($ch), curl_errno($ch));
    }

    curl_close($ch);


    dump($data);
});
