<?php
// config/liveconnect.php
// Centraliza toda la configuración de LiveConnect
// Los IDs dinámicos ya no están hardcodeados en el controlador

return [

    'api_url' => env('LIVECONNECT_API_URL', 'https://api.liveconnect.chat/prod'),
    'ckey' => env('LIVECONNECT_CKEY'),
    'private_key' => env('LIVECONNECT_PRIVATE_KEY'),

    // Cache del token (en segundos) - el token dura ~60 min
    'token_cache_ttl' => 3000,

    /*
    |--------------------------------------------------------------------------
    | Mapeo de campos dinámicos de LiveConnect
    |--------------------------------------------------------------------------
    | Clave = nombre legible que usamos internamente
    | Valor = ID críptico que usa LiveConnect
    |
    | Si LiveConnect cambia algún ID, solo se modifica aquí.
    */
    'dynamic_fields' => [
        'rubro'                     => 'dinamico5XRkX3',
        'tipo_sistema'              => 'dinamicoKebXw0',
        'nombre_comercial'          => 'dinamicoUHmNz4',
        'razon_social'              => 'dinamicoUoZap3',
        'id_cliente'                => 'dinamicoWW8jE5',
        'responsable'               => 'dinamicoyRZge3',
        'cuit'                      => 'dinamicoVTQod6',
        'clave_fiscal'              => 'dinamicoG8qKM7',
        'condicion_iva'             => 'dinamicodOqOH8',
        'pagina_web'                => 'dinamicoVzBq89',
        'instagram'                 => 'dinamico4LtUi10',
        'facebook'                  => 'dinamicoJaSJD11',
        'lic_principal'             => 'dinamicoKLnhC12',
        'lic_adicional'             => 'dinamicopygcm13',
        'lic_bonificada'            => 'dinamicoeJnw014',
        'implementacion_fecha'      => 'dinamicoZULEv15',
        'implementacion_comanderas' => 'dinamico5L0xX17',
        'tipo_facturacion'          => 'dinamicoZXEJG18',
        'tipo_conexion'             => 'dinamicocfL2d24',
        'implementacion_personal'   => 'dinamiconMKcb24',
        'implementacion_canales'    => 'dinamico6HxRe25',
        'implementacion_paneles'    => 'dinamicoyKeWP25',
        'lector_barras'             => 'dinamicoltYGr25',
        'motivo_descarte'           => 'dinamicorCaMp25',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapeo de condición IVA (texto ↔ ID)
    |--------------------------------------------------------------------------
    | LiveConnect envía texto, la DB guarda ID numérico
    */
    'condicion_iva_map' => [
        'Inscripto'           => 1,
        'No Inscripto'        => 2,
        'IVA no responsable'  => 3,
        'Excento'             => 4,
        'Consumidor Final'    => 5,
        'Monotributo'         => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapeo de tipo de facturación (texto ↔ ID)
    |--------------------------------------------------------------------------
    */
    'tipo_facturacion_map' => [
        'Facturación electrónica' => 1,
        'Controlador fiscal'      => 2,
        'Sin Facturación'         => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapeo de tipo de sistema (texto ↔ tipo)
    |--------------------------------------------------------------------------
    */
    'tipo_sistema_map' => [
        'PYME'        => ['tipo' => 1, 'pack' => 'GE'],
        'RESTO'       => ['tipo' => 2, 'pack' => 'RE'],
        'PEDIDOS BCN' => ['tipo' => 3, 'pack' => null],
    ],

];
