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
    public function index($tienda)
    {
        // El middleware ya configuró sesión y DB
        // Solo decidir si mostrar login o redirigir al catálogo

        if (TiendaService::esPrivada(session('tienda_modo')) && !session('tienda_comprador_id')) {
            return view('tienda.login', [
                'tienda' => $tienda,
                'nombreTienda' => session('tienda_nombre'),
            ]);
        }

        return redirect()->route('tienda.catalogo', $tienda);
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
                return redirect()->route('tienda.catalogo', $tienda);

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
