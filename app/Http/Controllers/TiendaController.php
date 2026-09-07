<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;
use App\Services\TiendaService;
use App\Services\TiendaCompradorService;
use App\Services\TiendaPagoService;

class TiendaController extends Controller
{
    /**
     * Link del preventista (F6): guarda el token en sesion y, si el comprador ya esta
     * logueado, arma el carrito; si no, lo manda a ingresar y el carrito se arma al entrar.
     */
    public function link($tienda, $token)
    {
        $links = new \App\Services\TiendaLinkService(session('tienda_prefix'));
        $r = $links->porToken($token);
        if (!$r['link']) {
            return view('tienda.mensaje', ['nombreTienda' => session('tienda_nombre'), 'titulo' => 'Este link no sirve', 'texto' => $r['motivo'], 'error' => true, 'icono' => 'fa-link',
                'url' => route('tienda.catalogo', $tienda), 'boton' => 'Ir al catálogo']);
        }
        $links->registrarApertura((int) $r['link']->id);
        session(['tienda_link_token' => $token]);

        if (session('tienda_comprador_web_id')) {
            return $this->aplicarLinkPendiente($tienda) ?: redirect()->route('tienda.carrito', $tienda);
        }

        return $this->vistaLogin($tienda, ['aviso' => 'Tu vendedor te armó un pedido. Ingresá con tu cuenta para revisarlo y confirmarlo.']);
    }

    /** Si hay un link pendiente en sesion, arma el carrito para el comprador logueado. Devuelve una respuesta o null. */
    private function aplicarLinkPendiente($tienda)
    {
        $token = session('tienda_link_token');
        if (!$token) {
            return null;
        }
        session()->forget('tienda_link_token');
        $links = new \App\Services\TiendaLinkService(session('tienda_prefix'));
        $r = $links->porToken($token);
        if (!$r['link']) {
            return null;
        }
        $link = $r['link'];
        if ((int) $link->codcli !== (int) session('tienda_comprador_id')) {
            return view('tienda.mensaje', ['nombreTienda' => session('tienda_nombre'), 'titulo' => 'Este pedido es de otro cliente', 'texto' => 'El link que abriste fue armado para otra cuenta. Si es tuyo, ingresá con el usuario correcto o pedile a tu vendedor que lo vuelva a mandar.', 'error' => true, 'icono' => 'fa-user-lock',
                'url' => route('tienda.catalogo', $tienda), 'boton' => 'Ir al catálogo']);
        }
        $armado = $links->armarCarrito($link, session('tienda_modo'));
        session([
            'tienda_carrito' => $armado['carrito'],
            'tienda_link' => ['id' => $link->id, 'codven' => $link->codven, 'vendedor' => $link->vendedor, 'salteados' => $armado['salteados'], 'observa' => $link->observa],
        ]);

        return redirect()->route('tienda.carrito', $tienda);
    }

    /**
     * Comprobante del cliente con el formato A4 de gestion (ventas.prg: PrintFactuElec /
     * FacturaNoFiscal). HTML imprimible, o PDF con ?pdf=1. Solo los propios.
     */
    public function comprobante($tienda, $ticomp, $letra, $numero)
    {
        $codcli = (int) session('tienda_comprador_id');
        if (!session('tienda_comprador_web_id') || !$codcli) {
            return redirect()->route('tienda.index', $tienda);
        }
        $srv = new \App\Services\TiendaFacturaService(session('tienda_prefix'));
        $f = $srv->datos($codcli, (string) $ticomp, (string) $letra, (string) $numero);
        if (!$f) {
            abort(404);
        }
        $pdf = request()->boolean('pdf');
        $clienteId = (int) session('tienda_config_id');
        $logoPath = public_path("img/logos/{$clienteId}/logo.png");
        $arcaPath = public_path('img/arca.jpg');
        $vista = [
            'f'    => $f,
            'pdf'  => $pdf,
            'logo' => file_exists($logoPath) ? ($pdf ? $logoPath : asset("img/logos/{$clienteId}/logo.png")) : null,
            'arca' => file_exists($arcaPath) ? ($pdf ? $arcaPath : asset('img/arca.jpg')) : null,
            'qr'   => \App\Services\TiendaFacturaService::qrDataUri($f['qr_url']),
        ];
        if ($pdf) {
            $nombre = preg_replace('/[^A-Za-z0-9-]/', '_', "{$ticomp}{$letra}-{$numero}") . '.pdf';

            return \Barryvdh\DomPDF\Facade\Pdf::loadView('tienda.factura', $vista)->setPaper('a4', 'portrait')->download($nombre);
        }

        return view('tienda.factura', $vista);
    }

    /** sitemap.xml de la tienda (F6). La privada devuelve uno vacio. */
    public function sitemap($tienda)
    {
        $xml = (new \App\Services\TiendaSeoService(session('tienda_prefix')))->sitemap($tienda, session('tienda_modo'));

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function index($tienda)
    {
        // El middleware ya configuró sesión y DB
        // Solo decidir si mostrar login o redirigir al catálogo

        if (TiendaService::esPrivada(session('tienda_modo')) && !session('tienda_comprador_web_id')) {
            return $this->vistaLogin($tienda);
        }

        return redirect()->route('tienda.catalogo', $tienda);
    }

    /** Pantalla de ingreso a pedido (en la tienda publica el login no es obligatorio). */
    public function ingresar($tienda)
    {
        if (session('tienda_comprador_web_id')) {
            return redirect()->route('tienda.catalogo', $tienda);
        }
        return $this->vistaLogin($tienda);
    }

    private function vistaLogin($tienda, array $extra = [])
    {
        return view('tienda.login', array_merge([
            'tienda' => $tienda,
            'nombreTienda' => session('tienda_nombre'),
            'permiteRegistro' => TiendaService::permiteRegistro(session('tienda_modo')),
        ], $extra));
    }

    // ------------------------------------------------------------ registro (F5)

    public function registroForm($tienda)
    {
        return view('tienda.registro', ['tienda' => $tienda, 'nombreTienda' => session('tienda_nombre')]);
    }

    public function registro(Request $request, $tienda)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'telefono' => 'nullable|string|max:40',
            'acepto' => 'accepted',
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña tiene que tener al menos 6 caracteres.',
            'acepto.accepted' => 'Tenés que aceptar los términos y condiciones.',
        ]);

        $requiereAprobacion = (bool) (session('tienda_datos')[TiendaService::CLAVE_REGISTRO_APROBACION] ?? false);
        $compradores = new TiendaCompradorService(session('tienda_prefix'));
        $r = $compradores->registrar($request->email, $request->password, $request->nombre, $request->telefono, $requiereAprobacion);

        if (!$r['ok']) {
            return back()->withInput($request->only('nombre', 'email', 'telefono'))->withErrors(['email' => $r['error']]);
        }

        $this->enviarMailValidacion($tienda, $r['comprador'], $r['token']);

        return view('tienda.mensaje', [
            'tienda' => $tienda, 'nombreTienda' => session('tienda_nombre'),
            'titulo' => 'Revisá tu correo',
            'texto' => "Te mandamos un enlace a {$r['comprador']->email} para confirmar tu cuenta. Si no lo ves, mirá en correo no deseado.",
            'icono' => 'fa-envelope-open-text',
        ]);
    }

    /** Enlace de validacion del mail (tambien reenvio desde el login). */
    public function validar($tienda, $token)
    {
        $compradores = new TiendaCompradorService(session('tienda_prefix'));
        $c = $compradores->validarEmail((string) $token);

        if (!$c) {
            return view('tienda.mensaje', [
                'tienda' => $tienda, 'nombreTienda' => session('tienda_nombre'),
                'titulo' => 'El enlace no sirve', 'texto' => 'Venció o ya se usó. Registrate de nuevo o pedí recuperar la contraseña.', 'icono' => 'fa-link-slash', 'error' => true,
            ]);
        }

        if ($c->estado === TiendaCompradorService::ESTADO_PENDIENTE) {
            return view('tienda.mensaje', [
                'tienda' => $tienda, 'nombreTienda' => session('tienda_nombre'),
                'titulo' => 'Mail confirmado', 'texto' => 'Tu cuenta queda pendiente de aprobación por el comercio. Te avisamos por mail cuando esté lista.', 'icono' => 'fa-user-clock',
            ]);
        }

        $compradores->iniciarSesion($c);

        return redirect()->route('tienda.catalogo', $tienda)->with('tienda_bienvenida', true);
    }

    public function recuperarForm($tienda)
    {
        return view('tienda.recuperar', ['tienda' => $tienda, 'nombreTienda' => session('tienda_nombre')]);
    }

    public function recuperar(Request $request, $tienda)
    {
        $request->validate(['email' => 'required|email']);
        $compradores = new TiendaCompradorService(session('tienda_prefix'));
        $c = $compradores->porEmail($request->email);

        // Siempre la misma respuesta: no se revela si el mail existe
        if ($c) {
            $token = $compradores->nuevoToken((int) $c->id);
            $this->enviarMailClave($tienda, $c, $token);
        }

        return view('tienda.mensaje', [
            'tienda' => $tienda, 'nombreTienda' => session('tienda_nombre'),
            'titulo' => 'Revisá tu correo', 'texto' => 'Si ese mail tiene una cuenta, te mandamos un enlace para elegir una contraseña nueva.', 'icono' => 'fa-envelope-open-text',
        ]);
    }

    public function nuevaClaveForm($tienda, $token)
    {
        $compradores = new TiendaCompradorService(session('tienda_prefix'));
        if (!$compradores->porToken((string) $token)) {
            return view('tienda.mensaje', [
                'tienda' => $tienda, 'nombreTienda' => session('tienda_nombre'),
                'titulo' => 'El enlace no sirve', 'texto' => 'Venció o ya se usó. Pedí uno nuevo desde "Olvidé mi contraseña".', 'icono' => 'fa-link-slash', 'error' => true,
            ]);
        }
        return view('tienda.nueva-clave', ['tienda' => $tienda, 'nombreTienda' => session('tienda_nombre'), 'token' => $token]);
    }

    public function nuevaClave(Request $request, $tienda, $token)
    {
        $request->validate(['password' => 'required|min:6|confirmed'], [
            'password.confirmed' => 'Las contraseñas no coinciden.', 'password.min' => 'La contraseña tiene que tener al menos 6 caracteres.',
        ]);
        $compradores = new TiendaCompradorService(session('tienda_prefix'));
        $c = $compradores->cambiarClave((string) $token, $request->password);
        if (!$c) {
            return back()->withErrors(['password' => 'El enlace venció. Pedí uno nuevo.']);
        }
        if ($c->estado === TiendaCompradorService::ESTADO_APROBADO) {
            $compradores->iniciarSesion($c);
            return redirect()->route('tienda.catalogo', $tienda);
        }
        return redirect()->route('tienda.index', $tienda);
    }

    // ------------------------------------------------ legales y arrepentimiento (F5)

    /** Datos del comercio, terminos y politica de privacidad (obligatorios para vender online). */
    public function legales($tienda)
    {
        $datos = session('tienda_datos', []);
        $contenido = view('tienda.legales', [
            'tienda' => $tienda,
            'datos' => $datos,
            'nombreTienda' => session('tienda_nombre'),
            'colores' => $this->colores($datos),
        ])->render();

        return view('layouts.tienda', ['slot' => new \Illuminate\Support\HtmlString($contenido)]);
    }

    /**
     * Boton de arrepentimiento (Res. 424/2020): el comprador pide revocar una compra
     * dentro de los 10 dias. Llega al comercio por mail y queda en el timeline del pedido.
     */
    public function arrepentimientoForm($tienda)
    {
        $datos = session('tienda_datos', []);
        $contenido = view('tienda.arrepentimiento', [
            'tienda' => $tienda, 'nombreTienda' => session('tienda_nombre'), 'colores' => $this->colores($datos),
            'enviado' => session('tienda_arrepentimiento_ok', false),
        ])->render();

        return view('layouts.tienda', ['slot' => new \Illuminate\Support\HtmlString($contenido)]);
    }

    public function arrepentimiento(Request $request, $tienda)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email',
            'pedido' => 'nullable|integer',
            'motivo' => 'nullable|string|max:1000',
        ]);

        $datos = session('tienda_datos', []);
        $prefijo = session('tienda_prefix');
        $pedidoId = (int) $request->pedido;

        if ($pedidoId > 0) {
            $existe = DB::connection('client_db')->table($prefijo . 'tienda_pedidos')->where('idpedido', $pedidoId)->exists();
            if ($existe) {
                (new \App\Services\TiendaPedidoService($prefijo))->registrarEvento(
                    $pedidoId, 'arrepentimiento',
                    'El comprador pidió revocar la compra (botón de arrepentimiento). ' . mb_substr((string) $request->motivo, 0, 150),
                    'comprador', $request->email
                );
            }
        }

        $emailComercio = $datos['email_comercio'] ?? null;
        if ($emailComercio) {
            $this->enviarMail($emailComercio, 'Botón de arrepentimiento - ' . session('tienda_nombre'), 'emails.tienda-cuenta', [
                'titulo' => 'Un comprador pidió revocar una compra',
                'texto' => "{$request->nombre} ({$request->email})" . ($pedidoId ? " sobre el pedido #{$pedidoId}" : '') . ' usó el botón de arrepentimiento. '
                    . ($request->motivo ? 'Motivo: ' . $request->motivo . '. ' : '')
                    . 'Tenés que responderle y, si corresponde, devolver el dinero dentro de los plazos legales.',
                'boton' => 'Ver pedidos web',
                'url' => url('/panel/panel?currentPage=fotos-productos'),
            ]);
        }

        return redirect()->route('tienda.arrepentimiento', $tienda)->with('tienda_arrepentimiento_ok', true);
    }

    private function colores(array $datos): array
    {
        return [
            'texto' => $datos['color_texto'] ?? '#1f2937',
            'precio' => $datos['color_precio'] ?? '#1E1B35',
            'boton' => $datos['color_boton'] ?? '#FFAF22',
        ];
    }

    private function enviarMailValidacion($tienda, object $comprador, string $token): void
    {
        $this->enviarMail($comprador->email, 'Confirmá tu cuenta en ' . session('tienda_nombre'), 'emails.tienda-cuenta', [
            'titulo' => 'Confirmá tu cuenta',
            'texto' => 'Gracias por registrarte en ' . session('tienda_nombre') . '. Tocá el botón para confirmar tu mail y empezar a comprar.',
            'boton' => 'Confirmar mi cuenta',
            'url' => route('tienda.validar', ['tienda' => $tienda, 'token' => $token]),
        ]);
    }

    private function enviarMailClave($tienda, object $comprador, string $token): void
    {
        $this->enviarMail($comprador->email, 'Recuperá tu contraseña en ' . session('tienda_nombre'), 'emails.tienda-cuenta', [
            'titulo' => 'Elegí una contraseña nueva',
            'texto' => 'Pediste recuperar la contraseña de tu cuenta en ' . session('tienda_nombre') . '. Si no fuiste vos, ignorá este mail.',
            'boton' => 'Elegir contraseña',
            'url' => route('tienda.nueva-clave', ['tienda' => $tienda, 'token' => $token]),
        ]);
    }

    private function enviarMail(string $para, string $asunto, string $vista, array $datos): void
    {
        try {
            $datosTienda = session('tienda_datos', []);
            \Illuminate\Support\Facades\Mail::send($vista, array_merge($datos, [
                'nombreTienda' => session('tienda_nombre'),
                'logoUrl' => session('tienda_logo'),
                'colorBoton' => $datosTienda['color_boton'] ?? '#FFAF22',
            ]), function ($m) use ($para, $asunto) {
                $m->to($para)->subject($asunto);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tienda: no se pudo enviar mail de cuenta: ' . $e->getMessage());
        }
    }

    public function login(Request $request, $tienda)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // El middleware ya configuró la DB. El ingreso es contra tienda_compradores
        // (contraseña hasheada); el viejo login por DNI de clientes quedó apagado.
        $compradores = new TiendaCompradorService(session('tienda_prefix'));
        $login = $compradores->autenticar($request->email, $request->password);

        switch ($login['resultado']) {
            case TiendaCompradorService::LOGIN_OK:
                $compradores->iniciarSesion($login['comprador']);
                return $this->aplicarLinkPendiente($tienda) ?: redirect()->route('tienda.catalogo', $tienda);

            case TiendaCompradorService::LOGIN_SIN_VALIDAR:
                // Se le reenvia el enlace de confirmacion
                $token = $compradores->nuevoToken((int) $login['comprador']->id);
                $this->enviarMailValidacion($tienda, $login['comprador'], $token);
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Todavía no confirmaste tu mail. Te reenviamos el enlace: revisá tu correo.']);

            case TiendaCompradorService::LOGIN_PENDIENTE:
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Tu cuenta todavía está pendiente de aprobación por el comercio.']);

            case TiendaCompradorService::LOGIN_RECHAZADO:
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Tu cuenta no está habilitada. Consultá con el comercio.']);

            default:
                return back()->withInput($request->only('email'))
                    ->withErrors(['password' => 'Email o contraseña incorrectos.']);
        }
    }

    /**
     * Pagina de pago de un pedido: a donde vuelve Mercado Pago (back_urls) y desde
     * donde el comprador puede reintentar el pago o ver los datos de transferencia.
     */
    public function pago($tienda, $pedido)
    {
        if (!session('tienda_comprador_web_id')) {
            return redirect()->route('tienda.index', $tienda);
        }

        $prefijo = session('tienda_prefix');
        $clienteId = (int) session('tienda_config_id');
        $pagos = new TiendaPagoService($prefijo);

        $sat = DB::connection('client_db')->table($prefijo . 'tienda_pedidos')->where('idpedido', (int) $pedido)->first();
        if (!$sat || (int) $sat->comprador_id !== (int) session('tienda_comprador_web_id')) {
            return redirect()->route('tienda.mis-pedidos', $tienda);
        }

        // Volviendo de MP: refrescar el estado antes de mostrar
        if ($sat->pago_medio === TiendaPagoService::MEDIO_MERCADOPAGO && $sat->pago_estado === TiendaPagoService::ESTADO_PENDIENTE) {
            $sat->pago_estado = $pagos->consultarMercadoPago((int) $pedido, $clienteId);
        }

        $total = (float) DB::connection('client_db')->table($prefijo . 'pedidos_det')
            ->where('idpedido', (int) $pedido)
            ->selectRaw('COALESCE(SUM(cantidad * (punitario - COALESCE(descu, 0))), 0) AS t')->value('t')
            + (float) $sat->envio_importe;

        $initPoint = null;
        if ($sat->pago_medio === TiendaPagoService::MEDIO_MERCADOPAGO && $sat->pago_estado !== TiendaPagoService::ESTADO_APROBADO && request()->boolean('reintentar')) {
            $items = DB::connection('client_db')->table($prefijo . 'pedidos_det')->where('idpedido', (int) $pedido)->get()
                ->map(fn ($d) => ['nombre' => $d->detart, 'cantidad' => (float) $d->cantidad, 'precio' => (float) $d->punitario - (float) ($d->descu ?? 0)])->all();
            $initPoint = $pagos->iniciarMercadoPago((int) $pedido, $clienteId, $tienda, $items, (float) $sat->envio_importe);
            if ($initPoint) {
                return redirect()->away($initPoint);
            }
        }

        $datos = session('tienda_datos', []);

        // layouts.tienda es un layout de Livewire (usa $slot): se le inyecta el contenido renderizado
        $contenido = view('tienda.pago', [
            'tienda' => $tienda,
            'pedidoId' => (int) $pedido,
            'sat' => $sat,
            'total' => $total,
            'transferencia' => $pagos->datosTransferencia(),
            'clienteId' => $clienteId,
            'colores' => [
                'texto' => $datos['color_texto'] ?? '#1f2937',
                'precio' => $datos['color_precio'] ?? '#1E1B35',
                'boton' => $datos['color_boton'] ?? '#FFAF22',
            ],
        ])->render();

        return view('layouts.tienda', ['slot' => new \Illuminate\Support\HtmlString($contenido)]);
    }

    public function logout($tienda)
    {
        session()->forget(array_merge(TiendaCompradorService::clavesSesion(), ['tienda_carrito']));

        return redirect()->route('tienda.index', $tienda);
    }
}
