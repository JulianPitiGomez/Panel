<?php

namespace App\Services;

use RuntimeException;

/**
 * Error al hablar con la API MiCorreo: sin credenciales, sin conexion, o una
 * respuesta de error del Correo. El mensaje esta pensado para mostrarse tal
 * cual en el panel del comercio.
 */
class CorreoArgentinoException extends RuntimeException
{
}
