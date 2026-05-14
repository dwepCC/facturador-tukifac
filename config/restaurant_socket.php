<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Socket.IO — puente Laravel → Node (deshabilitado en código)
    |--------------------------------------------------------------------------
    | La emisión real está comentada en RestaurantSocketBroadcaster::emit().
    | Aunque 'enabled' sea true, no se llamará al Node hasta reactivar ese método.
    */

    /*
    |--------------------------------------------------------------------------
    | Habilitar emisión hacia el puente Socket.IO (servidor Node)
    |--------------------------------------------------------------------------
    */
    'enabled' => env('RESTAURANT_SOCKET_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | URL interna del servidor Node (solo Laravel → Node, no es la URL pública del navegador)
    |--------------------------------------------------------------------------
    */
    'bridge_url' => env('SOCKET_BRIDGE_URL', 'http://127.0.0.1:8070'),

    /*
    |--------------------------------------------------------------------------
    | Secreto compartido Laravel ↔ Node (cabecera X-Restaurant-Socket-Secret)
    |--------------------------------------------------------------------------
    */
    'bridge_secret' => env('SOCKET_BRIDGE_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Timeout HTTP al puente (segundos)
    |--------------------------------------------------------------------------
    */
    'bridge_timeout' => (float) env('SOCKET_BRIDGE_TIMEOUT', 2),

];
