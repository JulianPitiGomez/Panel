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

    /*
    | Mostrar el item Catalogo / tienda a los comercios que NO la tienen
    | contratada, con la pantalla que les ofrece el servicio.
    | Apagado hasta que la tienda este terminada: si se sube el panel por otro
    | modulo, los clientes no tienen que ver nada ni empezar a preguntar.
    */
    'ofrecer_servicio' => (bool) env('TIENDA_OFRECER_SERVICIO', false),

    /*
    | Estados del pedido en GESTION (pedidos_encab.estado). Es la unica lista:
    | ningun componente repite estas letras. Fivewin es quien las escribe.
    |   P pendiente, A en armado, F facturado (los tres existen hoy)
    |   N anulado, R rechazado: confirmados el 2026-09-04 (los escribe Fivewin).
    |   Ver TiendaEstadoPedido.
    */
    'estados_gestion' => [
        'P' => 'Pendiente',
        'A' => 'En armado',
        'F' => 'Facturado',
        'N' => 'Anulado',
        'R' => 'Rechazado',
    ],

    // Estados que revierten la comision (la conciliacion los busca)
    'estados_anulados' => ['N', 'R'],

    // Estados que comprometen stock (F2): armado, pendiente y en proceso. Facturado ya descontó.
    'estados_comprometen_stock' => ['P', 'A'],

    /*
    | Comision: plazo en dias desde el primer pedido con saldo para pagar la
    | cuenta (mismo criterio que resto).
    */
    'comision_plazo_dias' => 31,

    /*
    | WhatsApp de BCN al que se manda al comercio que quiere contratar la
    | tienda (mismo numero que usan los logins del panel).
    */
    'contacto_whatsapp' => env('TIENDA_CONTACTO_WHATSAPP', '5491128654468'),

];
