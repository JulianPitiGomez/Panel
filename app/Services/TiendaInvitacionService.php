<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Invitacion a la tienda para los clientes de gestion que el comercio ya tiene con mail.
 *
 * El comprador invitado no tiene contrasena (password_hash vacio): el mail trae un
 * enlace a "elegir contrasena" (la misma pantalla de recuperar clave) que dura
 * TiendaCompradorService::HORAS_INVITACION. Al elegirla queda activo.
 *
 * La usan el comando tienda:sincronizar-compradores y el login (si un invitado
 * intenta entrar, se le reenvia).
 */
class TiendaInvitacionService
{
    public function __construct(private int $clienteId, private string $prefijo)
    {
    }

    /** Arma el enlace, lo guarda en el comprador y manda el mail. Devuelve si salio. */
    public function enviar(object $comprador): bool
    {
        $cliente = Cliente::find($this->clienteId);
        if (!$cliente || !$cliente->tienda) {
            return false;
        }

        $datos = TiendaService::configuracionWeb($cliente->id);
        $nombreTienda = trim((string) ($datos['nombre_fantasia'] ?? '')) ?: ($cliente->nomemp ?: $cliente->nombre);
        $logoPath = "img/logos/{$cliente->id}/logo.png";

        $compradores = new TiendaCompradorService($this->prefijo);
        $token = $compradores->nuevoToken((int) $comprador->id, TiendaCompradorService::HORAS_INVITACION);
        $base = rtrim(TiendaDominioService::urlPublica($cliente->tienda, $datos) ?? url('/web/' . $cliente->tienda), '/');

        try {
            Mail::send('emails.tienda-cuenta', [
                'titulo' => 'Te invitamos a nuestra tienda online',
                'texto' => 'Hola ' . trim((string) $comprador->nombre) . ': ahora podés hacer tus pedidos a ' . $nombreTienda
                    . ' desde la web, con tus precios de siempre. Para entrar, elegí tu contraseña con el botón. '
                    . 'Después ingresás con este mail (' . $comprador->email . ') y esa contraseña.',
                'boton' => 'Elegir mi contraseña',
                'url' => $base . '/nueva-clave/' . $token,
                'vence' => (TiendaCompradorService::HORAS_INVITACION / 24) . ' días',
                'nombreTienda' => $nombreTienda,
                'logoUrl' => file_exists(public_path($logoPath)) ? asset($logoPath) : '',
                'colorBoton' => $datos['color_boton'] ?? '#FFAF22',
            ], fn ($m) => $m->to($comprador->email)->subject("{$nombreTienda}: tu acceso a la tienda online"));

            return true;
        } catch (\Throwable $e) {
            // Sin mail no hay enlace: se deja para la proxima corrida
            \Illuminate\Support\Facades\DB::connection('client_db')->table($this->prefijo . 'tienda_compradores')
                ->where('id', $comprador->id)->update(['token_validacion' => null, 'token_vence' => null]);
            Log::error("Tienda: no se pudo mandar la invitacion al comprador {$comprador->id} del cliente {$this->clienteId}: " . $e->getMessage());

            return false;
        }
    }
}
