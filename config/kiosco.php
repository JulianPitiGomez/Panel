<?php

return [

    /*
    | Clientes (ids de config_clientes) que pueden usar el kiosco de autoservicio
    | de resto, separados por coma en KIOSCO_CLIENTES_HABILITADOS.
    | Por defecto solo el 10 (piloto). Vacio = todos los clientes resto.
    */
    'clientes_habilitados' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('KIOSCO_CLIENTES_HABILITADOS', '10'))
    ))),

];
