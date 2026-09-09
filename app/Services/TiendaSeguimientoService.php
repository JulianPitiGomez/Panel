<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Seguimiento del pedido web (F6): estados propios del ecommerce, separados del
 * estado de gestion que el comercio usa para facturar.
 *
 *   recibido -> preparacion -> listo -> (en_camino | despachado) -> entregado
 *
 * El paso de salida depende de como se entrega: flota propia va "en camino",
 * correo va "despachado" con numero de seguimiento, retiro salta directo a
 * "entregado" (retirado). Cada cambio deja su evento en tienda_eventos y le
 * avisa por mail al comprador. "cancelado" no lo pone el comercio desde aca:
 * llega de gestion (anulado / rechazado) por la conciliacion.
 */
class TiendaSeguimientoService
{
    public const RECIBIDO    = 'recibido';
    public const PREPARACION = 'preparacion';
    public const LISTO       = 'listo';
    public const DESPACHADO  = 'despachado';
    public const EN_CAMINO   = 'en_camino';
    public const ENTREGADO   = 'entregado';
    public const CANCELADO   = 'cancelado';

    private string $prefijo;
    private ?int $clienteId;

    public function __construct(string $prefijo, ?int $clienteId = null)
    {
        $this->prefijo = $prefijo;
        $this->clienteId = $clienteId;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    // ------------------------------------------------------------ catalogo de estados

    /** estado => etiqueta generica (config/tienda.php). */
    public static function estados(): array
    {
        return config('tienda.estados_web', []);
    }

    /** Lo guardado en tienda_pedidos.estado_web, tolerando lo viejo ('enviado' de F2). */
    public static function normalizar(?string $estado): string
    {
        if ($estado === 'enviado') {
            return self::DESPACHADO;
        }

        return array_key_exists((string) $estado, self::estados()) ? $estado : self::RECIBIDO;
    }

    /** Camino completo del pedido segun como se entrega. */
    public static function flujo(?string $entregaModo): array
    {
        return match ($entregaModo) {
            TiendaPedidoService::ENTREGA_RETIRO => [self::RECIBIDO, self::PREPARACION, self::LISTO, self::ENTREGADO],
            TiendaPedidoService::ENTREGA_FLOTA  => [self::RECIBIDO, self::PREPARACION, self::LISTO, self::EN_CAMINO, self::ENTREGADO],
            TiendaPedidoService::ENTREGA_CORREO => [self::RECIBIDO, self::PREPARACION, self::LISTO, self::DESPACHADO, self::ENTREGADO],
            default                             => [self::RECIBIDO, self::PREPARACION, self::LISTO, self::EN_CAMINO, self::ENTREGADO],
        };
    }

    /** Estados a los que el comercio puede pasar desde el actual (el siguiente del flujo; "a convenir" elige entre en camino y despachado). */
    public static function siguientes(?string $actual, ?string $entregaModo): array
    {
        $actual = self::normalizar($actual);
        if ($actual === self::CANCELADO || $actual === self::ENTREGADO) {
            return [];
        }
        $flujo = self::flujo($entregaModo);
        $i = array_search($actual, $flujo, true);
        if ($i === false) {
            return [self::PREPARACION];
        }
        $siguiente = $flujo[$i + 1] ?? null;
        if ($siguiente === null) {
            return [];
        }
        if ($actual === self::LISTO && $entregaModo === TiendaPedidoService::ENTREGA_A_CONVENIR) {
            return [self::EN_CAMINO, self::DESPACHADO, self::ENTREGADO];
        }

        return [$siguiente];
    }

    /** Estado previo en el flujo (para deshacer un clic de mas), o null. */
    public static function anterior(?string $actual, ?string $entregaModo): ?string
    {
        $actual = self::normalizar($actual);
        if (in_array($actual, [self::CANCELADO, self::RECIBIDO], true)) {
            return null;
        }
        $flujo = self::flujo($entregaModo);
        $i = array_search($actual, $flujo, true);
        if ($i === false) {
            // en_camino / despachado fuera de su flujo (a convenir): vuelve a listo
            return self::LISTO;
        }

        return $flujo[$i - 1] ?? null;
    }

    /** Etiqueta para mostrar; con el modo de entrega se afina ("Listo para retirar", "Retirado"). */
    public static function etiqueta(?string $estado, ?string $entregaModo = null): string
    {
        $estado = self::normalizar($estado);
        if ($entregaModo === TiendaPedidoService::ENTREGA_RETIRO) {
            if ($estado === self::LISTO) {
                return 'Listo para retirar';
            }
            if ($estado === self::ENTREGADO) {
                return 'Retirado';
            }
        }
        if ($estado === self::LISTO && in_array($entregaModo, [TiendaPedidoService::ENTREGA_FLOTA, TiendaPedidoService::ENTREGA_CORREO], true)) {
            return 'Listo para salir';
        }

        return self::estados()[$estado] ?? ucfirst($estado);
    }

    /** Texto que se le dice al comprador en el mail de cada estado. */
    public static function textoComprador(string $estado, ?string $entregaModo, ?string $seguimiento = null): string
    {
        return match (self::normalizar($estado)) {
            self::RECIBIDO    => 'Recibimos tu pedido y en breve empezamos a prepararlo.',
            self::PREPARACION => 'Ya estamos preparando tu pedido.',
            self::LISTO       => $entregaModo === TiendaPedidoService::ENTREGA_RETIRO
                ? 'Tu pedido está listo. Podés pasar a retirarlo por el local.'
                : 'Tu pedido está listo y sale en el próximo envío.',
            self::EN_CAMINO   => 'Tu pedido salió y está en camino a tu dirección.',
            self::DESPACHADO  => $seguimiento
                ? "Tu pedido fue despachado por correo. Número de seguimiento: {$seguimiento}."
                : 'Tu pedido fue despachado por correo.',
            self::ENTREGADO   => $entregaModo === TiendaPedidoService::ENTREGA_RETIRO
                ? '¡Gracias por tu compra! Registramos que retiraste tu pedido.'
                : '¡Gracias por tu compra! Tu pedido fue entregado.',
            self::CANCELADO   => 'Tu pedido fue cancelado. Si no lo pediste vos, escribinos y lo vemos.',
            default           => '',
        };
    }

    /** Link publico de seguimiento del transportista, cuando lo tiene. */
    public static function urlSeguimiento(?string $transportista, ?string $numero): ?string
    {
        $numero = trim((string) $numero);
        if ($numero === '') {
            return null;
        }
        $plantilla = config('tienda.seguimiento_urls.' . (string) $transportista);

        return $plantilla ? str_replace('{nro}', rawurlencode($numero), $plantilla) : null;
    }

    // ------------------------------------------------------------ cambios

    /**
     * El comercio cambia el estado desde el panel. Devuelve ['ok' => bool, 'mensaje' => string].
     * Para despachar por correo hace falta el numero de seguimiento.
     */
    public function cambiar(int $idpedido, string $estado, ?string $seguimiento, string $usuario): array
    {
        $sat = $this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->first();
        if (!$sat) {
            return ['ok' => false, 'mensaje' => 'El pedido no vino de la tienda web.'];
        }
        $estado = self::normalizar($estado);
        $actual = self::normalizar($sat->estado_web);
        $permitidos = array_merge(self::siguientes($actual, $sat->entrega_modo), array_filter([self::anterior($actual, $sat->entrega_modo)]));
        if (!in_array($estado, $permitidos, true)) {
            return ['ok' => false, 'mensaje' => 'Ese cambio de estado no corresponde para este pedido.'];
        }

        $seguimiento = mb_substr(trim((string) $seguimiento), 0, 60);
        if ($estado === self::DESPACHADO && $sat->entrega_modo === TiendaPedidoService::ENTREGA_CORREO && $seguimiento === '' && !$sat->envio_seguimiento) {
            return ['ok' => false, 'mensaje' => 'Para marcar despachado por correo cargá el número de seguimiento.'];
        }

        $cambios = ['estado_web' => $estado];
        if ($seguimiento !== '') {
            $cambios['envio_seguimiento'] = $seguimiento;
        }
        $this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->update($cambios);

        $nro = $seguimiento !== '' ? $seguimiento : $sat->envio_seguimiento;
        $detalle = self::etiqueta($estado, $sat->entrega_modo);
        if ($estado === self::DESPACHADO && $nro) {
            $detalle .= ". Número de seguimiento: {$nro}";
        }
        $eventos = new TiendaPedidoService($this->prefijo);
        $eventos->registrarEvento($idpedido, $estado, $detalle, 'comercio', $usuario);

        // Volver atras no avisa: seria confundir al comprador con un "listo" que no era.
        // El evento queda como notificado para que tienda:avisos-pendientes tampoco lo mande.
        $esAvance = in_array($estado, self::siguientes($actual, $sat->entrega_modo), true);
        if (!$esAvance) {
            $this->tabla('tienda_eventos')->where('idpedido', $idpedido)->where('estado', $estado)
                ->orderByDesc('id')->limit(1)->update(['notificado' => 1]);
        }
        $avisado = $esAvance && $this->avisarEstado($idpedido, $estado, $nro);

        return ['ok' => true, 'mensaje' => "Pedido #{$idpedido}: {$detalle}." . ($avisado ? ' Se le avisó al comprador por mail.' : '')];
    }

    /** Gestion anulo o rechazo el pedido (lo detecta la conciliacion): estado cancelado + aviso. */
    public function cancelarPorGestion(int $idpedido, string $motivo): void
    {
        $sat = $this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->first();
        if (!$sat || self::normalizar($sat->estado_web) === self::CANCELADO) {
            return;
        }
        $this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->update(['estado_web' => self::CANCELADO]);
        (new TiendaPedidoService($this->prefijo))->registrarEvento($idpedido, self::CANCELADO, "Cancelado: {$motivo}", 'sistema');
        $this->avisarEstado($idpedido, self::CANCELADO, null);
    }

    // ------------------------------------------------------------ aviso por mail

    /** El comprador de este pedido tiene un mail al que avisarle. */
    public function tieneDestinatario(int $idpedido): bool
    {
        return $this->emailComprador($this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->first()) !== null;
    }

    /**
     * Manda el aviso del estado al comprador (mail y, si el comercio lo tiene, WhatsApp) y
     * marca el evento como notificado: el de $eventoId si viene (comando tienda:avisos-pendientes,
     * para los cambios hechos desde gestion), si no el ultimo de ese estado. Nunca rompe el flujo.
     */
    public function avisarEstado(int $idpedido, string $estado, ?string $seguimiento, ?int $eventoId = null): bool
    {
        try {
            $sat = $this->tabla('tienda_pedidos')->where('idpedido', $idpedido)->first();
            $email = $this->emailComprador($sat);
            if (!$email) {
                return false;
            }
            $ctx = $this->contexto();
            if (!$ctx) {
                return false;
            }

            $flujo = self::flujo($sat->entrega_modo);
            $pos = array_search($estado, $flujo, true);
            $pasos = [];
            foreach ($flujo as $i => $e) {
                $pasos[] = ['etiqueta' => self::etiqueta($e, $sat->entrega_modo), 'hecho' => $pos !== false && $i <= $pos, 'actual' => $e === $estado];
            }

            Mail::send('emails.tienda-pedido-estado', [
                'nombreTienda'   => $ctx['nombre'],
                'logoUrl'        => $ctx['logo'],
                'colorBoton'     => $ctx['color'],
                'idpedido'       => $idpedido,
                'estado'         => $estado,
                'etiqueta'       => self::etiqueta($estado, $sat->entrega_modo),
                'texto'          => self::textoComprador($estado, $sat->entrega_modo, $seguimiento),
                'pasos'          => $estado === self::CANCELADO ? [] : $pasos,
                'urlPedidos'     => $ctx['url_pedidos'],
                'urlSeguimiento' => self::urlSeguimiento($sat->envio_transportista, $seguimiento ?: $sat->envio_seguimiento),
                'seguimiento'    => $seguimiento ?: $sat->envio_seguimiento,
            ], function ($m) use ($email, $ctx, $idpedido, $sat, $estado) {
                $m->to($email)->subject("Pedido #{$idpedido} en {$ctx['nombre']}: " . self::etiqueta($estado, $sat->entrega_modo));
            });

            if ($eventoId) {
                $this->tabla('tienda_eventos')->where('id', $eventoId)->update(['notificado' => 1]);
            } else {
                $this->tabla('tienda_eventos')->where('idpedido', $idpedido)->where('estado', $estado)
                    ->orderByDesc('id')->limit(1)->update(['notificado' => 1]);
            }

            // WhatsApp (F7): si el comercio lo tiene activo, el mismo aviso por ahi
            try {
                $wa = new TiendaWhatsappService($this->prefijo);
                $codcli = DB::connection('client_db')->table($this->prefijo . 'pedidos_encab')->where('id', $idpedido)->value('codcli');
                $nombre = (string) ($sat->comprador_id ? $this->tabla('tienda_compradores')->where('id', $sat->comprador_id)->value('nombre') : '');
                $wa->avisarEstado($idpedido, $wa->telefonoComprador($sat->comprador_id ? (int) $sat->comprador_id : null, $codcli ? (int) $codcli : null), $nombre, self::etiqueta($estado, $sat->entrega_modo), $ctx['nombre']);
            } catch (\Throwable $e) {
                Log::warning('Tienda: WhatsApp del estado del pedido ' . $idpedido . ': ' . $e->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Tienda: no se pudo avisar el estado del pedido {$idpedido}: " . $e->getMessage());

            return false;
        }
    }

    /** Mail del comprador web; si el pedido lo hizo un cliente importado sin cuenta web, el de clientes. */
    private function emailComprador(?object $sat): ?string
    {
        if (!$sat) {
            return null;
        }
        $email = $sat->comprador_id
            ? $this->tabla('tienda_compradores')->where('id', $sat->comprador_id)->value('email')
            : null;
        if (!$email) {
            $codcli = DB::connection('client_db')->table($this->prefijo . 'pedidos_encab')->where('id', $sat->idpedido)->value('codcli');
            $email = $codcli ? trim((string) $this->tabla('clientes')->where('CODIGO', $codcli)->value('MAIL')) : null;
        }

        return $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /** Nombre, logo, color y link de la tienda para el mail (funciona desde el panel, sin sesion de tienda). */
    private function contexto(): ?array
    {
        $cliente = $this->clienteId ? Cliente::find($this->clienteId) : null;
        if (!$cliente) {
            return null;
        }
        $datos = TiendaService::configuracionWeb($cliente->id);
        $logoPath = "img/logos/{$cliente->id}/logo.png";

        return [
            'nombre'      => $cliente->nomemp ?: $cliente->nombre,
            'logo'        => file_exists(public_path($logoPath)) ? asset($logoPath) : '',
            'color'       => $datos['color_boton'] ?? '#FFAF22',
            'url_pedidos' => $cliente->tienda ? route('tienda.mis-pedidos', $cliente->tienda) : url('/'),
        ];
    }
}
