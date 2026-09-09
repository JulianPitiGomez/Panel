<?php

/**
 * API MiCorreo de Correo Argentino (cotizacion e importacion de envios de la tienda).
 *
 * Las credenciales NO van aca: son de cada comercio y viven cifradas en
 * {prefijo}tienda_integraciones (servicio 'correo'). Aca solo lo comun a todos.
 */
return [
    // Ambientes. Cual se usa lo elige cada comercio al cargar sus credenciales.
    'urls' => [
        'test' => 'https://apitest.correoargentino.com.ar/micorreo/v1',
        'produccion' => 'https://api.correoargentino.com.ar/micorreo/v1',
    ],

    // Segundos de espera para conectar y para la respuesta del Correo.
    'timeout' => (int) env('CORREO_TIMEOUT', 15),

    // Un articulo sin peso o medidas cotizaria con cero y la API lo rechaza. Cada
    // comercio puede cargar su propio bulto por defecto en Envios (peso_default y
    // bulto_default); esto es el ultimo recurso cuando tampoco lo hizo.
    'bulto_por_defecto' => [
        'peso_gramos' => 500,
        'ancho_cm' => 25,
        'alto_cm' => 10,
        'largo_cm' => 30,
    ],

    // Limites que impone /rates: peso en gramos y lados en centimetros.
    'limites' => [
        'peso_min_gramos' => 1,
        'peso_max_gramos' => 25000,
        'lado_max_cm' => 150,
    ],
];
