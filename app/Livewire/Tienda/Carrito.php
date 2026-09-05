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

    // Entrega (F2): modalidad elegida, direccion y cotizacion (ver TiendaEnvioService)
    public $entregaModo = '';
    public $opcionesEntrega = [];
    public $direccion = ['calle' => '', 'numero' => '', 'piso' => '', 'localidad' => '', 'provincia' => '', 'cp' => '', 'referencia' => '', 'lat' => null, 'lng' => null];
    public $direccionesGuardadas = [];
    public $direccionSeleccionada = '';
    public $guardarDireccion = true;
    public $envioImporte = 0;
    public $entregaError = '';

    // Pago (F2): medio elegido y opciones (ver TiendaPagoService)
    public $pagoMedio = '';
    public $opcionesPago = [];
    public $pagoError = '';
    public $pagoInfo = [];   // para la pantalla de exito: medio, importe unico, datos de transferencia, aviso MP

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
            $this->cargarDireccionesGuardadas();
        }
    }

    // ------------------------------------------------------------ entrega

    /**
     * Recalcula las opciones de entrega y el costo de envio para el carrito actual.
     * Se llama en cada render: el costo depende del subtotal (minimo bonificado)
     * y de la direccion, y asi hay un solo lugar que lo resuelve.
     */
    private function cotizarEntrega()
    {
        $this->setupClientDatabase();

        if ($this->direccion['lat'] === null && $this->necesitaCoordenadas()) {
            $this->geocodificarDireccion();
        }

        $srv = new \App\Services\TiendaEnvioService($this->tablePrefix);
        $this->opcionesEntrega = $srv->opciones(
            (float) $this->getTotal(),
            $this->direccion['lat'] !== null ? (float) $this->direccion['lat'] : null,
            $this->direccion['lng'] !== null ? (float) $this->direccion['lng'] : null,
            $this->direccion['cp'] ?: null
        );

        // Modo elegido invalido o no disponible: pasar al primero disponible
        if (!isset($this->opcionesEntrega[$this->entregaModo]) || !$this->opcionesEntrega[$this->entregaModo]['disponible']) {
            $this->entregaModo = '';
            foreach ($this->opcionesEntrega as $modo => $op) {
                if ($op['disponible']) {
                    $this->entregaModo = $modo;
                    break;
                }
            }
        }

        $this->envioImporte = $this->entregaModo ? (float) $this->opcionesEntrega[$this->entregaModo]['importe'] : 0.0;

        $this->cotizarPago();
    }

    /** Medios de pago disponibles para el total actual (con envio) y el comprador logueado. */
    private function cotizarPago()
    {
        $codcli = session('tienda_comprador_web_id') ? (session('tienda_comprador_id') ? (int) session('tienda_comprador_id') : null) : null;
        $this->opcionesPago = (new \App\Services\TiendaPagoService($this->tablePrefix))
            ->opciones((float) $this->getTotal() + (float) $this->envioImporte, $codcli);

        if (!isset($this->opcionesPago[$this->pagoMedio]) || !$this->opcionesPago[$this->pagoMedio]['disponible']) {
            $this->pagoMedio = '';
            foreach ($this->opcionesPago as $medio => $op) {
                if ($op['disponible']) {
                    $this->pagoMedio = $medio;
                    break;
                }
            }
        }
    }

    /** Hay zonas por radio o poligono y una direccion escrita sin coordenadas? */
    private function necesitaCoordenadas(): bool
    {
        if (trim($this->direccion['calle']) === '' || trim($this->direccion['localidad']) === '') {
            return false;
        }

        $zonas = (new \App\Services\TiendaEnvioService($this->tablePrefix))->zonas(true);
        foreach ($zonas as $z) {
            if ($z->tipo !== \App\Services\TiendaEnvioService::ZONA_CP) {
                return true;
            }
        }
        return false;
    }

    private function geocodificarDireccion()
    {
        $texto = trim("{$this->direccion['calle']} {$this->direccion['numero']}, {$this->direccion['localidad']}, {$this->direccion['provincia']}, Argentina");
        $coords = \App\Services\TiendaEnvioService::geocodificar($texto);
        if ($coords) {
            [$this->direccion['lat'], $this->direccion['lng']] = $coords;
        }
    }

    /** Cualquier cambio en la direccion escrita invalida las coordenadas (se vuelven a resolver). */
    public function updatedDireccion($valor, $campo)
    {
        if (in_array($campo, ['calle', 'numero', 'localidad', 'provincia', 'cp'], true)) {
            $this->direccion['lat'] = null;
            $this->direccion['lng'] = null;
            $this->direccionSeleccionada = '';
        }
    }

    /** Lo llama el autocompletado de Google desde el navegador. */
    public function setDireccionDesdeMapa(array $d)
    {
        // Si venia de una direccion guardada, piso y referencia eran de esa: no arrastrarlos a la nueva
        if ($this->direccionSeleccionada !== '') {
            $this->direccion['piso'] = '';
            $this->direccion['referencia'] = '';
            $this->guardarDireccion = true;
        }
        foreach (['calle', 'numero', 'localidad', 'provincia', 'cp'] as $k) {
            if (isset($d[$k])) {
                $this->direccion[$k] = mb_substr((string) $d[$k], 0, 100);
            }
        }
        $this->direccion['lat'] = is_numeric($d['lat'] ?? null) ? (float) $d['lat'] : null;
        $this->direccion['lng'] = is_numeric($d['lng'] ?? null) ? (float) $d['lng'] : null;
        $this->direccionSeleccionada = '';
    }

    private function cargarDireccionesGuardadas()
    {
        $webId = session('tienda_comprador_web_id');
        if (!$webId) {
            $this->direccionesGuardadas = [];
            return;
        }

        $this->setupClientDatabase();
        $this->direccionesGuardadas = DB::connection('client_db')
            ->table("{$this->tablePrefix}tienda_direcciones")
            ->where('comprador_id', $webId)
            ->orderByDesc('predeterminada')->orderBy('id')
            ->get()
            ->map(fn ($d) => (array) $d)
            ->all();

        // Si todavia no escribio nada, precargar la predeterminada
        if (empty($this->direccionesGuardadas) || trim($this->direccion['calle']) !== '') {
            return;
        }
        $this->seleccionarDireccion($this->direccionesGuardadas[0]['id']);
    }

    public function seleccionarDireccion($id)
    {
        foreach ($this->direccionesGuardadas as $d) {
            if ($d['id'] == $id) {
                $this->direccion = [
                    'calle' => $d['calle'], 'numero' => $d['numero'] ?? '', 'piso' => $d['piso'] ?? '',
                    'localidad' => $d['localidad'], 'provincia' => $d['provincia'] ?? '', 'cp' => $d['cp'] ?? '',
                    'referencia' => $d['referencia'] ?? '', 'lat' => null, 'lng' => null,
                ];
                $this->direccionSeleccionada = (string) $id;
                $this->guardarDireccion = false;
                return;
            }
        }
    }

    /** "Calle 123, piso 2 (ref)" para el satelite, el mail y la observacion del pedido. */
    private function textoDireccion(bool $conLocalidad = false): string
    {
        $d = $this->direccion;
        $t = trim($d['calle'] . ' ' . $d['numero']);
        if (trim($d['piso']) !== '') {
            $t .= ', ' . trim($d['piso']);
        }
        if ($conLocalidad && trim($d['localidad']) !== '') {
            $t .= ', ' . trim($d['localidad']) . (trim($d['cp']) !== '' ? " ({$d['cp']})" : '');
        }
        if (trim($d['referencia']) !== '') {
            $t .= ' - ' . trim($d['referencia']);
        }
        return $t;
    }

    private function guardarDireccionComprador()
    {
        $webId = session('tienda_comprador_web_id');
        if (!$webId || !$this->guardarDireccion || trim($this->direccion['calle']) === '') {
            return;
        }

        $tabla = DB::connection('client_db')->table("{$this->tablePrefix}tienda_direcciones");
        $repetida = $tabla->where('comprador_id', $webId)
            ->whereRaw('LOWER(calle) = ?', [mb_strtolower(trim($this->direccion['calle']))])
            ->where('numero', trim($this->direccion['numero']) ?: null)
            ->whereRaw('LOWER(localidad) = ?', [mb_strtolower(trim($this->direccion['localidad']))])
            ->exists();
        if ($repetida) {
            return;
        }

        DB::connection('client_db')->table("{$this->tablePrefix}tienda_direcciones")->insert([
            'comprador_id' => $webId,
            'calle' => mb_substr(trim($this->direccion['calle']), 0, 100),
            'numero' => mb_substr(trim($this->direccion['numero']), 0, 10) ?: null,
            'piso' => mb_substr(trim($this->direccion['piso']), 0, 20) ?: null,
            'localidad' => mb_substr(trim($this->direccion['localidad']), 0, 60),
            'provincia' => mb_substr(trim($this->direccion['provincia']), 0, 40) ?: null,
            'cp' => mb_substr(trim($this->direccion['cp']), 0, 10) ?: null,
            'referencia' => mb_substr(trim($this->direccion['referencia']), 0, 200) ?: null,
            'predeterminada' => empty($this->direccionesGuardadas) ? 1 : 0,
        ]);
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
        $this->cargarDireccionesGuardadas();

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

        // Revalidar stock al confirmar: entre armar el carrito y confirmar pueden pasar horas
        if (!\App\Services\TiendaStockService::vendeSinStock(session('tienda_datos', []))) {
            $faltantes = (new \App\Services\TiendaStockService($this->tablePrefix))->faltantes($this->carrito);
            if (!empty($faltantes)) {
                $lista = collect($faltantes)->map(fn ($f) => "{$f['nombre']} (pediste " . \App\Services\TiendaStockService::formato($f['pedido']) . ", hay " . \App\Services\TiendaStockService::formato($f['disponible']) . ")")->implode('; ');
                session()->flash('error', "No hay stock suficiente para: {$lista}. Ajustá las cantidades.");
                return;
            }
        }

        $resumenIva = \App\Services\TiendaPrecioService::resumenIva($this->carrito);

        // Entrega: recotizar con el servidor como verdad (no confiar en lo que quedo en pantalla)
        $this->entregaError = '';
        $this->cotizarEntrega();
        $requiereDireccion = in_array($this->entregaModo, [\App\Services\TiendaPedidoService::ENTREGA_FLOTA, \App\Services\TiendaPedidoService::ENTREGA_A_CONVENIR], true);
        if (!$this->entregaModo) {
            $this->entregaError = 'Elegí cómo querés recibir el pedido.';
            return;
        }
        if ($requiereDireccion && (trim($this->direccion['calle']) === '' || trim($this->direccion['localidad']) === '')) {
            $this->entregaError = 'Completá calle y localidad de la dirección de entrega.';
            return;
        }
        $opcion = $this->opcionesEntrega[$this->entregaModo];
        $entregaResumen = $opcion['etiqueta'];
        if ($requiereDireccion) {
            $entregaResumen .= ': ' . $this->textoDireccion(true);
        }
        if ($this->envioImporte > 0) {
            $entregaResumen .= ' ($' . number_format($this->envioImporte, 2, ',', '.') . ')';
        }

        // Pago: el medio tiene que existir y estar disponible para este comprador y total
        $this->pagoError = '';
        if (!$this->pagoMedio || !isset($this->opcionesPago[$this->pagoMedio]) || !$this->opcionesPago[$this->pagoMedio]['disponible']) {
            $this->pagoError = 'Elegí cómo vas a pagar.';
            return;
        }
        $pagoMedio = $this->pagoMedio;
        $totalConEnvio = round((float) $this->getTotal() + (float) $this->envioImporte, 2);

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
        $pagoRegistrado = [];
        $pedidoId = DB::connection('client_db')->transaction(function () use ($compradorId, $compradorNombre, $compradorVendedor, $vendedorNombre, $compradorDescuento, $resumenIva, $opcion, $entregaResumen, $requiereDireccion, $pagoMedio, $totalConEnvio, &$pagoRegistrado) {

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
                // Misma columna y mismo texto base de siempre; se agrega como se entrega para que gestion lo vea
                'observa' => mb_substr(($this->observaciones ? "Pedido Tienda Web: {$this->observaciones}" : 'Pedido desde Tienda Web') . ' | ' . $entregaResumen, 0, 200),
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
        (new \App\Services\TiendaPedidoService($this->tablePrefix))->registrarSatelite($pedidoId, (int) session('tienda_config_id'), [
            'iva_incluido'      => $resumenIva['iva'],
            'entrega_modo'      => $this->entregaModo,
            'entrega_direccion' => $requiereDireccion ? mb_substr($this->textoDireccion(), 0, 160) : null,
            'entrega_localidad' => $requiereDireccion ? mb_substr(trim($this->direccion['localidad']), 0, 60) : null,
            'entrega_cp'        => $requiereDireccion ? (mb_substr(trim($this->direccion['cp']), 0, 10) ?: null) : null,
            'entrega_zona_id'   => $opcion['zona_id'],
            'envio_importe'     => $this->envioImporte,
        ]);

        if ($requiereDireccion) {
            $this->guardarDireccionComprador();
        }

        $pagoRegistrado = (new \App\Services\TiendaPagoService($this->tablePrefix))
            ->registrarPagoInicial($pedidoId, $pagoMedio, $totalConEnvio, session('tienda_comprador_email'));

        return $pedidoId;
        });

        $entregaParaEmail = $entregaResumen;
        $envioParaEmail = $this->envioImporte;

        // Datos de pago para la pantalla de exito y el mail
        $pagos = new \App\Services\TiendaPagoService($this->tablePrefix);
        $this->pagoInfo = [
            'medio' => $pagoMedio,
            'etiqueta' => \App\Services\TiendaPagoService::etiquetaMedio($pagoMedio),
            'importe_unico' => $pagoRegistrado['pago_importe_unico'] ?? null,
            'transferencia' => $pagoMedio === \App\Services\TiendaPagoService::MEDIO_TRANSFERENCIA ? $pagos->datosTransferencia() : null,
            'total' => $totalConEnvio,
            'mp_url' => null,
        ];

        // Mercado Pago: crear la preferencia y mandar al comprador a pagar
        if ($pagoMedio === \App\Services\TiendaPagoService::MEDIO_MERCADOPAGO) {
            $items = array_map(fn ($i) => ['nombre' => $i['nombre'], 'cantidad' => (float) $i['cantidad'], 'precio' => (float) $i['precio']], $this->carrito);
            $this->pagoInfo['mp_url'] = $pagos->iniciarMercadoPago($pedidoId, (int) session('tienda_config_id'), $this->tiendaSlug, $items, (float) $this->envioImporte);
        }

        // Preparar datos para email antes de limpiar
        $itemsParaEmail = $this->carrito;
        $totalEmail = collect($this->carrito)->sum('subtotal');

        // Enviar emails
        $this->enviarEmails($pedidoId, $compradorNombre, $itemsParaEmail, $totalEmail, $entregaParaEmail, $envioParaEmail);

        // Limpiar carrito
        session()->forget('tienda_carrito');
        $this->carrito = [];
        $this->pedidoConfirmado = true;
        $this->numeroPedido = $pedidoId;

        if (!empty($this->pagoInfo['mp_url'])) {
            return $this->redirect($this->pagoInfo['mp_url']);
        }
    }

    private function enviarEmails($pedidoId, $clienteNombre, $items, $total, $entrega = '', $envio = 0)
    {
        $pago = $this->pagoInfo;
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
                'total' => $total + $envio,
                'subtotalProductos' => $total,
                'envio' => $envio,
                'entrega' => $entrega,
                'pago' => $pago,
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
        if (!$this->pedidoConfirmado && !empty($this->carrito)) {
            $this->cotizarEntrega();
        }

        return view('tienda.carrito', [
            'total' => $this->getTotal(),
            'totalConEnvio' => $this->getTotal() + $this->envioImporte,
            'resumenIva' => \App\Services\TiendaPrecioService::resumenIva($this->carrito),
            'googleMapsApiKey' => config('app.google_maps_api_key'),
        ]);
    }
}
