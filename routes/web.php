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

Route::middleware('auth:sanctum')->get('/show', 'App\Http\Controllers\TradeController@show');
Route::middleware('auth:sanctum')->post('/buy', 'App\Http\Controllers\TradeController@buy');
Route::middleware('auth:sanctum')->post('/sell', 'App\Http\Controllers\TradeController@sell');
Route::middleware('auth:sanctum')->delete('/remove', 'App\Http\Controllers\TradeController@remove');
Route::middleware('auth:sanctum')->patch('/update', 'App\Http\Controllers\TradeController@update');
Route::middleware('auth:sanctum')->get('/portfolio', 'App\Http\Controllers\TradeController@portfolio');
Route::middleware('auth:sanctum')->get('/show', 'App\Http\Controllers\TradeController@show');
Route::middleware('auth:sanctum')->get('/returns', 'App\Http\Controllers\TradeController@returns');
