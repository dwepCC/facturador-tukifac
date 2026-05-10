<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Socket.IO — sin grupo middleware "api" (evita throttle por IP del Node)
|--------------------------------------------------------------------------
|
| Las peticiones POST llegan desde el servidor ws.* hacia el tenant; todas
| comparten la misma IP externa. El throttle:60,1 del grupo api agotaba el
| cupo y devolvía 429 → el cliente veía connect_error: unauthorized.
*/

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);
if ($hostname) {
    Route::domain($hostname->fqdn)->group(function () {
        Route::post('/restaurant/socket/auth', 'RestaurantSocketAuthController@authenticate');
    });
}
