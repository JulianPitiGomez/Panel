<?php

namespace App\Livewire\Tienda;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Cliente;

class Carrito extends Component
{
    public $tiendaSlug;
    public $tablePrefix;
    public $nombreTienda;
    public $modo; // TiendaService::MODO_PUBLICA | MODO_PRIVADA

    public $carrito = [];
    public $emailComprador = '';
    public $passwordComprador = '';
    public $parafecha;
    public $observaciones = '';

    public $compradorValidado = false;
    public $compradorNombre = '';
    public $compradorError = '';

    public $pedidoConfirmado = false;
    public $numeroPedido = null;

    public $colores = [];

    public function mount($tienda)
    {
        $this->tiendaSlug = $tienda;
        $this->setupClientDatabase();

        $this->nombreTienda = session('tienda_nombre');
        $this->modo = \App\Services\TiendaService::normalizarModo(session('tienda_modo'));
        $this->carrito = session('tienda_carrito', []);
        $this->parafecha = date('Y-m-d');

        $datos = session('tienda_datos', []);
        $this->colores = [
            'fondo' => $datos['color_fondo'] ?? '#f9fafb',
            'texto' => $datos['color_texto'] ?? '#1f2937',
            'precio' => $datos['color_precio'] ?? '#1E1B35',
            'boton' => $datos['color_boton'] ?? '#FFAF22',
        ];

        // Comprador ya logueado (por el login de la tienda o desde este mismo carrito)
        if (session('tienda_comprador_web_id')) {
            $this->compradorValidado = true;
            $this->compradorNombre = session('tienda_comprador_nombre');
        }
    }

    private function setupClientDatabase()
    {
        $configId = session('tienda_config_id');
        if (!$configId) {
            return redirect()->route('tienda.index', $this->tiendaSlug);
        }

        $cliente = Cliente::find($configId);
        if ($cliente) {
            Config::set('database.connections.client_db.database', $cliente->base);
            DB::purge('client_db');
            $this->tablePrefix = $cliente->getTablePrefix();
        }
    }

    public function actualizarCantidad($index, $cantidad)
    {
        if ($cantidad < 1) {
            $this->eliminarItem($index);
            return;
        }

        if (isset($this->carrito[$index])) {
            $this->carrito[$index]['cantidad'] = $cantidad;
            $this->carrito[$index]['subtotal'] = $cantidad * $this->carrito[$index]['precio'];
            session(['tienda_carrito' => $this->carrito]);
        }
    }

    public function incrementarCantidad($index)
    {
        if (isset($this->carrito[$index])) {
            $this->carrito[$index]['cantidad']++;
            $this->carrito[$index]['subtotal'] = $this->carrito[$index]['cantidad'] * $this->carrito[$index]['precio'];
            session(['tienda_carrito' => $this->carrito]);
        }
    }

    public function decrementarCantidad($index)
    {
        if (isset($this->carrito[$index])) {
            if ($this->carrito[$index]['cantidad'] <= 1) {
                $this->eliminarItem($index);
                return;
            }
            $this->carrito[$index]['cantidad']--;
            $this->carrito[$index]['subtotal'] = $this->carrito[$index]['cantidad'] * $this->carrito[$index]['precio'];
            session(['tienda_carrito' => $this->carrito]);
        }
    }

    public function eliminarItem($index)
    {
        unset($this->carrito[$index]);
        $this->carrito = array_values($this->carrito);
        session(['tienda_carrito' => $this->carrito]);
    }

    public function vaciarCarrito()
    {
        $this->carrito = [];
        session(['tienda_carrito' => []]);
    }

    public function getTotal()
    {
        return collect($this->carrito)->sum('subtotal');
    }

    /**
     * Ingreso del comprador desde el carrito (tienda publica): mail + contrasena
     * contra tienda_compradores, igual que el login de la tienda privada.
     * Identificarse solo por mail dejaba usar la lista de precios de otro cliente.
     */
    public function ingresarComprador()
    {
        $this->compradorError = '';

        if (empty($this->emailComprador) || empty($this->passwordComprador)) {
            $this->compradorError = 'Ingresá tu email y tu contraseña.';
            return;
        }

        $this->setupClientDatabase();

        $compradores = new \App\Services\TiendaCompradorService($this->tablePrefix);
        $login = $compradores->autenticar($this->emailComprador, $this->passwordComprador);
        $this->passwordComprador = '';

        switch ($login['resultado']) {
            case \App\Services\TiendaCompradorService::LOGIN_OK:
                $compradores->iniciarSesion($login['comprador']);
                break;
            case \App\Services\TiendaCompradorService::LOGIN_PENDIENTE:
                $this->compradorError = 'Tu cuenta todavía está pendiente de aprobación por el comercio.';
                return;
            case \App\Services\TiendaCompradorService::LOGIN_RECHAZADO:
                $this->compradorError = 'Tu cuenta no está habilitada. Consultá con el comercio.';
                return;
            default:
                $this->compradorError = 'Email o contraseña incorrectos.';
                return;
        }

        $this->compradorValidado = true;
        $this->compradorNombre = session('tienda_comprador_nombre');

        // Recalcular precios según la lista del comprador
        $this->recalcularPrecios();
    }

    private function recalcularPrecios()
    {
        $this->setupClientDatabase();

        $lispreesp = session('tienda_comprador_lispreesp');
        $lispre = session('tienda_comprador_lispre');

        foreach ($this->carrito as $key => $item) {
            $nuevoPrecio = null;

            // Primero verificar lista especial
            if ($lispreesp) {
                $especial = DB::connection('client_db')
                    ->table("{$this->tablePrefix}lispredet")
                    ->where('CODLIS', $lispreesp)
                    ->where('CODART', $item['codigo'])
                    ->value('PRECIO');

                if ($especial !== null) {
                    $nuevoPrecio = $especial;
                }
            }

            // Si no hay precio especial, usar lista general
            if ($nuevoPrecio === null) {
                $nuevoPrecio = $lispre == 2 ? $item['precio_rev'] : $item['precio_ven'];
            }

            $this->carrito[$key]['precio'] = $nuevoPrecio;
            $this->carrito[$key]['subtotal'] = $nuevoPrecio * $item['cantidad'];
        }

        session(['tienda_carrito' => $this->carrito]);
    }

    public function confirmarPedido()
    {
        if (empty($this->carrito)) {
            session()->flash('error', 'El carrito esta vacio');
            return;
        }

        if (!$this->compradorValidado || !session('tienda_comprador_web_id')) {
            $this->compradorValidado = false;
            $this->compradorError = 'Ingresá con tu email y contraseña antes de confirmar.';
            return;
        }

        // Validar fecha
        if ($this->parafecha < date('Y-m-d')) {
            session()->flash('error', 'La fecha de entrega no puede ser anterior a hoy');
            return;
        }

        $this->setupClientDatabase();

        $compradorId = session('tienda_comprador_id');
        $compradorNombre = session('tienda_comprador_nombre');
        $compradorVendedor = session('tienda_comprador_vendedor');
        $compradorDescuento = session('tienda_comprador_descuento', 0);

        // Obtener nombre del vendedor
        $vendedorNombre = 'TIENDA WEB';
        if ($compradorVendedor) {
            $vendedor = DB::connection('client_db')
                ->table("{$this->tablePrefix}vendedores")
                ->where('CODIGO', $compradorVendedor)
                ->first();
            if ($vendedor) {
                $vendedorNombre = $vendedor->NOMBRE;
            }
        }

        // Encabezado, detalle y satelite en una sola transaccion: o entra todo o no entra nada.
        // Lo que va a pedidos_encab / pedidos_det es exactamente lo de siempre (regla del proyecto).
        $pedidoId = DB::connection('client_db')->transaction(function () use ($compradorId, $compradorNombre, $compradorVendedor, $vendedorNombre, $compradorDescuento) {

        $pedidoId = DB::connection('client_db')
            ->table("{$this->tablePrefix}pedidos_encab")
            ->insertGetId([
                'codcli' => $compradorId,
                'cliente' => $compradorNombre,
                'codven' => $compradorVendedor ?: '0',
                'vendedor' => $vendedorNombre,
                'fecha' => date('Y-m-d'),
                'parafecha' => $this->parafecha,
                'estado' => 'P',
                'observa' => $this->observaciones ? "Pedido Tienda Web: {$this->observaciones}" : 'Pedido desde Tienda Web',
                'nuevo' => true,
            ]);

        // Insertar detalle del pedido
        foreach ($this->carrito as $item) {
            $descup = $compradorDescuento;
            $descu = round($item['precio'] * $descup / 100, 2);

            DB::connection('client_db')
                ->table("{$this->tablePrefix}pedidos_det")
                ->insert([
                    'idpedido' => $pedidoId,
                    'codart' => $item['codigo'],
                    'detart' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'cantidadreal' => $item['cantidad'],
                    'punitario' => $item['precio'],
                    'descu' => $descu,
                    'descup' => $descup,
                ]);
        }

        // Fila satelite del ecommerce (comprador web, entrega, pago, comision, estado web)
        (new \App\Services\TiendaPedidoService($this->tablePrefix))->registrarSatelite($pedidoId, (int) session('tienda_config_id'));

        return $pedidoId;
        });

        // Preparar datos para email antes de limpiar
        $itemsParaEmail = $this->carrito;
        $totalEmail = collect($this->carrito)->sum('subtotal');

        // Enviar emails
        $this->enviarEmails($pedidoId, $compradorNombre, $itemsParaEmail, $totalEmail);

        // Limpiar carrito
        session()->forget('tienda_carrito');
        $this->carrito = [];
        $this->pedidoConfirmado = true;
        $this->numeroPedido = $pedidoId;
    }

    private function enviarEmails($pedidoId, $clienteNombre, $items, $total)
    {
        try {
            $datos = session('tienda_datos', []);
            $emailComercio = $datos['email_comercio'] ?? null;
            $nombreTienda = $this->nombreTienda;
            $logoUrl = session('tienda_logo');
            $colorBoton = $this->colores['boton'];

            $mailData = [
                'pedidoId' => $pedidoId,
                'clienteNombre' => $clienteNombre,
                'items' => $items,
                'total' => $total,
                'fecha' => date('Y-m-d'),
                'parafecha' => $this->parafecha,
                'observaciones' => $this->observaciones,
                'nombreTienda' => $nombreTienda,
                'logoUrl' => $logoUrl,
                'colorBoton' => $colorBoton,
                'datos' => $datos,
            ];

            // Email al comercio
            $emailCliente = $this->emailComprador ?: session('tienda_comprador_email');
            if ($emailComercio) {
                Mail::send('emails.pedido', array_merge($mailData, ['esComerciante' => true]), function ($m) use ($emailComercio, $emailCliente, $nombreTienda, $pedidoId) {
                    $m->to($emailComercio)
                      ->subject("Nuevo pedido #{$pedidoId} - {$nombreTienda}");
                    if ($emailCliente) {
                        $m->replyTo($emailCliente);
                    }
                });
            }

            // Email al cliente
            if ($emailCliente) {
                Mail::send('emails.pedido', array_merge($mailData, ['esComerciante' => false]), function ($m) use ($emailCliente, $emailComercio, $nombreTienda, $pedidoId) {
                    $m->to($emailCliente)
                      ->subject("Pedido #{$pedidoId} confirmado - {$nombreTienda}");
                    if ($emailComercio) {
                        $m->replyTo($emailComercio, $nombreTienda);
                    }
                });
            }
        } catch (\Exception $e) {
            Log::error('Error enviando email de pedido: ' . $e->getMessage());
        }
    }

    public function volverAlCatalogo()
    {
        return redirect()->route('tienda.catalogo', $this->tiendaSlug);
    }

    public function irAMisPedidos()
    {
        return redirect()->route('tienda.mis-pedidos', $this->tiendaSlug);
    }

    #[Layout('layouts.tienda')]
    public function render()
    {
        return view('tienda.carrito', [
            'total' => $this->getTotal(),
        ]);
    }
}
