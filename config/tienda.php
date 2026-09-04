<?php

/*
|--------------------------------------------------------------------------
| Tienda web PyMEs
|--------------------------------------------------------------------------
|
| Configuracion central del modulo de tienda. Ninguna pantalla ni middleware
| decide por su cuenta si un cliente tiene tienda: todos preguntan a
| App\Services\TiendaService, que lee de aca.
|
*/

return [

    /*
    | Clientes con la tienda habilitada durante el piloto, por id de
    | config_clientes, separados por coma en TIENDA_CLIENTES_HABILITADOS.
    | Vacio = habilitada para todos los clientes GE3 con slug cargado.
    | Cuando se abra al resto, basta con dejar la variable vacia.
    */
    'clientes_habilitados' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('TIENDA_CLIENTES_HABILITADOS', ''))
    ))),

    /*
    | Modos de acceso a la tienda. Se guardan en la clave "tienda_modo" del
    | JSON de config_clientes_web.configuracion (antes vivia en
    | config_clientes.codven, que es el vendedor comercial del CRM).
    */
    'modos' => [
        'publica' => 'Acceso libre (cualquiera puede ver)',
        'privada' => 'Privado (requiere usuario y contraseña)',
    ],

    'modo_default' => 'publica',

];
