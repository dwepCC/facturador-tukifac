<?php

use Illuminate\Support\Facades\Route;
use Modules\Item\Http\Controllers\Api\ItemCatalogController;

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if ($hostname)
{
    Route::domain($hostname->fqdn)->group(function () {

        Route::middleware(['auth:api', 'locked.tenant'])->group(function () {

            Route::prefix('items')->group(function () {

                // Route::post('update', 'Api\ItemController@update');
                Route::get('export/stock', 'ItemController@ItemExportStock');
                Route::get('catalog', [ItemCatalogController::class, 'index']);

            });

        });
    });
}