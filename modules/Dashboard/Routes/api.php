<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/dashboard', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:api'])->prefix('dashboard')->group(function () {
    Route::get('global-data', 'DashboardController@globalData');
    Route::post('data', 'DashboardController@data');
    Route::post('data_aditional', 'DashboardController@data_aditional');
    Route::get('filter', 'DashboardController@filter');
    Route::get('stock-by-product/records', 'DashboardController@stockByProduct');
    Route::get('product-of-due/records', 'DashboardController@productOfDue');
    Route::post('utilities', 'DashboardController@utilities');
});