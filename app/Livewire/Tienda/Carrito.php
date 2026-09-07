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

    // Agenda (F2): cuando lo quiere (ver TiendaAgendaService)
    public $agenda = [];           // disponibilidad() para el carrito y la modalidad actual
    public $entregaFecha = '';     // dia elegido (Y-m-d)
    public $entregaFranjaId = null; // franja elegida (0 = todo el dia / null = lo antes posible)
    public $loAntesPosible = false;
    public $agendaError = '';

    // Promociones (F3): lineas negativas calculadas como en gestion (ver TiendaPromoService)
    public $promoLineas = [];
    public $promoDescuento = 0;
    public $promoExcluido = false;

    // Pago (F2): medio elegido y opciones (ver TiendaPagoService)
    public $pagoMedio = '';
    public $opcionesPago = [];
    public $pagoError = '';
    public $pagoInfo = [];   // para la pantalla de exito: medio, importe unico, datos de transferencia, aviso MP

    public $colores = [];

    public $paso = '';             // checkout por pasos (cuenta > entrega > cuando > pago > confirmar)

    public $linkVendedor = null;   // ['id','codven','vendedor','salteados','observa'] cuando el carrito vino de un link del vendedor

    public function mount($tienda)
    {
        $this->tiendaSlug = $tienda;
        $this->setupClientDatabase();

        $this->nombreTienda = session('tienda_nombre');
        $this->modo = \App\Services\TiendaService::normalizarModo(session('tienda_modo'));
        $this->carrito = session('tienda_carrito', []);
        $this->linkVendedor = session('tienda_link');   // pedido armado por el preventista (F6)
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

    // ------------------------------------------------------------ checkout por pasos

    /** Pasos del checkout para esta tienda y este comprador, en orden (clave => titulo). */
    public function pasos(): array
    {
        $pasos = [];
        if (!$this->compradorValidado) {
            $pasos['cuenta'] = 'Tu cuenta';
        }
        $unicaSinDireccion = count($this->opcionesEntrega) === 1 && !$this->necesitaDireccion(array_key_first($this->opcionesEntrega));
        if (count($this->opcionesEntrega) && !$unicaSinDireccion) {
            $pasos['entrega'] = 'Entrega';
        }
        $pasos['cuando'] = 'Cuándo';
        if (count($this->opcionesPago) > 1) {
            $pasos['pago'] = 'Pago';
        }
        $pasos['confirmar'] = 'Confirmar';

        return $pasos;
    }

    public function necesitaDireccion(?string $modo): bool
    {
        return in_array($modo, [\App\Services\TiendaPedidoService::ENTREGA_FLOTA, \App\Services\TiendaPedidoService::ENTREGA_CORREO, \App\Services\TiendaPedidoService::ENTREGA_A_CONVENIR], true);
    }

    public function direccionCompleta(): bool
    {
        return trim((string) $this->direccion['calle']) !== '' && trim((string) $this->direccion['localidad']) !== '';
    }

    /** Mensaje de error si el paso no esta completo; null si esta bien. */
    private function validarPaso(string $paso): ?string
    {
        switch ($paso) {
            case 'cuenta':
                return $this->compradorValidado ? null : 'Ingresá con tu cuenta para seguir.';
            case 'entrega':
                if (!$this->entregaModo || !isset($this->opcionesEntrega[$this->entregaModo])) {
                    return 'Elegí cómo querés recibir el pedido.';
                }
                if ($this->necesitaDireccion($this->entregaModo) && !$this->direccionCompleta()) {
                    return 'Completá calle y localidad de la dirección de entrega.';
                }
                if (!$this->opcionesEntrega[$this->entregaModo]['disponible']) {
                    return $this->opcionesEntrega[$this->entregaModo]['detalle'] ?: 'Esa forma de entrega no está disponible para tu dirección.';
                }
                return null;
            case 'cuando':
                if (!empty($this->agenda['activa'])) {
                    return (new \App\Services\TiendaAgendaService($this->tablePrefix))->validar(
                        $this->carrito, $this->entregaModo ?: 'retiro', $this->entregaFecha ?: null,
                        $this->entregaFranjaId !== null ? (int) $this->entregaFranjaId : null
                    );
                }
                return ($this->parafecha && $this->parafecha >= date('Y-m-d')) ? null : 'Elegí una fecha de entrega de hoy en adelante.';
            case 'pago':
                return ($this->pagoMedio && !empty($this->opcionesPago[$this->pagoMedio]['disponible'])) ? null : 'Elegí cómo vas a pagar.';
        }

        return null;
    }

    /** Primer paso que falta completar (el ultimo es 'confirmar'). */
    private function primerPasoIncompleto(): string
    {
        foreach (array_keys($this->pasos()) as $k) {
            if ($k !== 'confirmar' && $this->validarPaso($k) !== null) {
                return $k;
            }
        }

        return 'confirmar';
    }

    /** Se corre en cada render: si el paso actual dejo de existir o quedo adelantado, se acomoda. */
    private function ajustarPaso(): void
    {
        $claves = array_keys($this->pasos());
        // Se arranca siempre por el primer paso (aunque lo guardado ya lo deje valido: el comprador lo revisa)
        if ($this->paso === '' || !in_array($this->paso, $claves, true)) {
            $this->paso = $claves[0];
        }
        // Nunca mas adelante de lo que falta completar
        $tope = $this->primerPasoIncompleto();
        if (array_search($this->paso, $claves, true) > array_search($tope, $claves, true)) {
            $this->paso = $tope;
        }
    }

    public function irAPaso($paso)
    {
        $this->setupClientDatabase();
        $this->cotizarEntrega();
        $claves = array_keys($this->pasos());
        if (!in_array($paso, $claves, true)) {
            return;
        }
        $tope = $this->primerPasoIncompleto();
        if (array_search($paso, $claves, true) <= array_search($tope, $claves, true)) {
            $this->paso = $paso;
        }
    }

    public function siguientePaso()
    {
        $this->setupClientDatabase();
        $this->cotizarEntrega();
        $this->entregaError = '';
        $this->agendaError = '';
        $this->pagoError = '';
        $error = $this->validarPaso($this->paso);
        if ($error !== null) {
            match ($this->paso) {
                'entrega' => $this->entregaError = $error,
                'cuando'  => $this->agendaError = $error,
                'pago'    => $this->pagoError = $error,
                default   => $this->compradorError = $error,
            };
            return;
        }
        $claves = array_keys($this->pasos());
        $i = array_search($this->paso, $claves, true);
        $this->paso = $claves[$i + 1] ?? 'confirmar';
    }

    public function pasoAnterior()
    {
        $claves = array_keys($this->pasos());
        $i = array_search($this->paso, $claves, true);
        if ($i !== false && $i > 0) {
            $this->paso = $claves[$i - 1];
        }
    }

    /** Textos cortos de lo elegido, para el paso de confirmacion. */
    public function resumenEleccion(): array
    {
        $entrega = $this->entregaModo && isset($this->opcionesEntrega[$this->entregaModo]) ? $this->opcionesEntrega[$this->entregaModo]['etiqueta'] : '';
        if ($entrega && $this->necesitaDireccion($this->entregaModo) && $this->direccionCompleta()) {
            $entrega .= ' · ' . $this->textoDireccion(true);
        }
        $cuando = '';
        if (!empty($this->agenda['activa'])) {
            if ($this->loAntesPosible && !empty($this->agenda['lo_antes_posible'])) {
                $cuando = $this->agenda['lo_antes_posible']['etiqueta'];
            } elseif ($this->entregaFecha) {
                $dia = collect($this->agenda['dias'] ?? [])->firstWhere('fecha', $this->entregaFecha);
                $franja = $dia ? collect($dia['franjas'])->firstWhere('id', (int) $this->entregaFranjaId) : null;
                $cuando = ($dia['etiqueta'] ?? \Carbon\Carbon::parse($this->entregaFecha)->format('d/m')) . ($franja ? ' · ' . $franja['etiqueta'] : '');
            }
        } elseif ($this->parafecha) {
            $cuando = \Carbon\Carbon::parse($this->parafecha)->format('d/m/Y');
        }
        $pago = $this->pagoMedio && isset($this->opcionesPago[$this->pagoMedio]) ? $this->opcionesPago[$this->pagoMedio]['etiqueta'] : '';

        return ['entrega' => $entrega, 'cuando' => $cuando, 'pago' => $pago];
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
            $this->direccion['cp'] ?: null,
            $this->direccion['provincia'] ?: null,
            $this->carrito
        );

        // Modo elegido invalido o no disponible: pasar al primero disponible. Excepcion: un envio que
        // todavia no se pudo cotizar porque falta la direccion se mantiene elegido (el paso pide la direccion).
        $pendienteDireccion = isset($this->opcionesEntrega[$this->entregaModo]) && $this->necesitaDireccion($this->entregaModo) && !$this->direccionCompleta();
        if (!$pendienteDireccion && (!isset($this->opcionesEntrega[$this->entregaModo]) || !$this->opcionesEntrega[$this->entregaModo]['disponible'])) {
            $this->entregaModo = '';
            foreach ($this->opcionesEntrega as $modo => $op) {
                if ($op['disponible']) {
                    $this->entregaModo = $modo;
                    break;
                }
            }
        }

        $this->envioImporte = $this->entregaModo ? (float) $this->opcionesEntrega[$this->entregaModo]['importe'] : 0.0;

        $this->cotizarAgenda();
        $this->cotizarPago();
    }

    /** Dias y franjas disponibles para este carrito y modalidad; ajusta la eleccion si dejo de valer. */
    private function cotizarAgenda()
    {
        $this->agenda = (new \App\Services\TiendaAgendaService($this->tablePrefix))
            ->disponibilidad($this->carrito, $this->entregaModo ?: \App\Services\TiendaPedidoService::ENTREGA_RETIRO);

        if (!$this->agenda['activa']) {
            return;
        }

        if ($this->loAntesPosible && $this->agenda['lo_antes_posible']) {
            $this->entregaFecha = $this->agenda['lo_antes_posible']['fecha'];
            $this->entregaFranjaId = $this->agenda['lo_antes_posible']['franja_id'];
            return;
        }
        $this->loAntesPosible = false;

        // La fecha elegida sigue ofrecida?
        $dia = collect($this->agenda['dias'])->firstWhere('fecha', $this->entregaFecha);
        if (!$dia) {
            $this->entregaFecha = '';
            $this->entregaFranjaId = null;
            return;
        }
        $franja = collect($dia['franjas'])->first(fn ($f) => (int) $f['id'] === (int) $this->entregaFranjaId && $f['disponible']);
        if (!$franja) {
            $this->entregaFranjaId = null;
        }
    }

    public function elegirDia($fecha)
    {
        $this->loAntesPosible = false;
        $this->entregaFecha = (string) $fecha;
        $this->entregaFranjaId = null;
        // Si el dia tiene una sola franja disponible, queda elegida
        $dia = collect($this->agenda['dias'] ?? [])->firstWhere('fecha', $this->entregaFecha);
        $disponibles = $dia ? array_values(array_filter($dia['franjas'], fn ($f) => $f['disponible'])) : [];
        if (count($disponibles) === 1) {
            $this->entregaFranjaId = (int) $disponibles[0]['id'];
        }
    }

    public function elegirFranja($id)
    {
        $this->loAntesPosible = false;
        $this->entregaFranjaId = (int) $id;
    }

    public function elegirLoAntesPosible()
    {
        $this->loAntesPosible = true;
    }

    /** Medios de pago disponibles para el total actual (con envio) y el comprador logueado. */
    private function cotizarPago()
    {
        $codcli = session('tienda_comprador_web_id') ? (session('tienda_comprador_id') ? (int) session('tienda_comprador_id') : null) : null;
        $pagos = new \App\Services\TiendaPagoService($this->tablePrefix);

        // Las promos dependen de la forma de pago: primero se elige el medio con el total bruto,
        // despues se recalculan las promos con ese medio y se vuelve a validar el total.
        $this->opcionesPago = $pagos->opciones((float) $this->getTotal() + (float) $this->envioImporte, $codcli);
        if (!isset($this->opcionesPago[$this->pagoMedio]) || !$this->opcionesPago[$this->pagoMedio]['disponible']) {
            $this->pagoMedio = '';
            foreach ($this->opcionesPago as $medio => $op) {
                if ($op['disponible']) {
                    $this->pagoMedio = $medio;
                    break;
                }
            }
        }

        $this->calcularPromos($pagos->forpagDe($this->pagoMedio ?: null), $codcli);

        if ($this->promoDescuento > 0) {
            $this->opcionesPago = $pagos->opciones($this->getTotalConPromos() + (float) $this->envioImporte, $codcli);
        }
    }

    /** Promos de gestion para el carrito con la forma de pago elegida (linea negativa aparte, como gestion). */
    private function calcularPromos(?object $forpag, ?int $codcli)
    {
        $r = (new \App\Services\TiendaPromoService($this->tablePrefix))->calcular($this->carrito, $forpag, $codcli);
        $this->promoLineas = $r['lineas'];
        $this->promoDescuento = $r['descuento'];
        $this->promoExcluido = $r['excluido'];
    }

    /** Subtotal de productos menos las promos (sin envio). */
    public function getTotalConPromos(): float
    {
        return round((float) $this->getTotal() - (float) $this->promoDescuento, 2);
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
        (new \App\Services\TiendaFavoritoService($this->tablePrefix))->fusionarSesion();
        $this->paso = '';   // el render lo lleva al siguiente paso

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

        $this->setupClientDatabase();

        // Agenda: con agenda activa, la fecha sale de lo elegido y se revalida contra el cupo real
        $this->agendaError = '';
        $franjaTexto = null;
        $consumeCupo = false;
        $agendaSrv = new \App\Services\TiendaAgendaService($this->tablePrefix);
        $this->cotizarEntrega();   // deja $this->agenda al dia con la modalidad actual
        if ($this->agenda['activa']) {
            $error = $agendaSrv->validar($this->carrito, $this->entregaModo ?: 'retiro', $this->entregaFecha ?: null, $this->entregaFranjaId !== null ? (int) $this->entregaFranjaId : null);
            if ($error) {
                $this->agendaError = $error;
                return;
            }
            $this->parafecha = $this->entregaFecha;
            $dia = collect($this->agenda['dias'])->firstWhere('fecha', $this->entregaFecha);
            $franja = $dia ? collect($dia['franjas'])->firstWhere('id', (int) $this->entregaFranjaId) : null;
            $franjaTexto = $franja['etiqueta'] ?? null;
            $consumeCupo = $agendaSrv->carritoConsumeCupo($this->carrito);
        } elseif (!$this->agenda['abierta'] && !$agendaSrv->config()['recibe_cerrada']) {
            $this->agendaError = 'La tienda está cerrada y no está tomando pedidos.';
            return;
        }

        // Validar fecha
        if ($this->parafecha < date('Y-m-d')) {
            session()->flash('error', 'La fecha de entrega no puede ser anterior a hoy');
            return;
        }

        // Lo que dejo de venderse (NOSALE) o el comercio oculto desde que se armo el carrito, se saca
        $codigos = array_values(array_unique(array_map(fn ($i) => (string) $i['codigo'], array_filter($this->carrito, fn ($i) => ($i['cantidad'] ?? 0) > 0))));
        if (!empty($codigos)) {
            $vendibles = DB::connection('client_db')
                ->table("{$this->tablePrefix}articu as a")
                ->leftJoin("{$this->tablePrefix}tienda_articu as ta", 'a.CODIGO', '=', 'ta.codigo')
                ->whereIn('a.CODIGO', $codigos)
                ->where('a.NOSALE', 0)
                ->whereRaw('COALESCE(ta.visible_web, 1) = 1')
                ->pluck('a.CODIGO')->map(fn ($c) => (string) $c)->all();
            $noVendibles = array_diff($codigos, $vendibles);
            if (!empty($noVendibles)) {
                $nombres = collect($this->carrito)->whereIn('codigo', $noVendibles)->pluck('nombre')->unique()->implode(', ');
                $this->carrito = array_values(array_filter($this->carrito, fn ($i) => !in_array((string) $i['codigo'], $noVendibles)));
                session(['tienda_carrito' => $this->carrito]);
                session()->flash('error', "Estos productos ya no están disponibles y se quitaron del carrito: {$nombres}. Revisá el pedido y confirmá de nuevo.");
                return;
            }
        }

        // Revalidar stock al confirmar: entre armar el carrito y confirmar pueden pasar horas
        if (!\App\Services\TiendaStockService::vendeSinStock(session('tienda_datos', []))) {
            $faltantes = (new \App\Services\TiendaStockService($this->tablePrefix))->faltantes($this->carrito);
            if (!empty($faltantes)) {
                $lista = collect($faltantes)->map(fn ($f) => "{$f['nombre']} (pediste " . \App\Services\TiendaStockService::formato($f['pedido']) . ", hay " . \App\Services\TiendaStockService::formato($f['disponible']) . ")")->implode('; ');
                session()->flash('error', "No hay stock suficiente para: {$lista}. Ajustá las cantidades.");
                return;
            }
        }

        $resumenIva = \App\Services\TiendaPrecioService::resumenIva($this->carrito, $this->promoLineas);

        // Entrega: recotizar con el servidor como verdad (no confiar en lo que quedo en pantalla)
        $this->entregaError = '';
        $requiereDireccion = in_array($this->entregaModo, [\App\Services\TiendaPedidoService::ENTREGA_FLOTA, \App\Services\TiendaPedidoService::ENTREGA_CORREO, \App\Services\TiendaPedidoService::ENTREGA_A_CONVENIR], true);
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
        if ($franjaTexto) {
            $entregaResumen .= ' · ' . \Carbon\Carbon::parse($this->parafecha)->format('d/m') . ' ' . $franjaTexto;
        }

        // Pago: el medio tiene que existir y estar disponible para este comprador y total
        $this->pagoError = '';
        if (!$this->pagoMedio || !isset($this->opcionesPago[$this->pagoMedio]) || !$this->opcionesPago[$this->pagoMedio]['disponible']) {
            $this->pagoError = 'Elegí cómo vas a pagar.';
            return;
        }
        $pagoMedio = $this->pagoMedio;
        $totalConEnvio = round($this->getTotalConPromos() + (float) $this->envioImporte, 2);
        $promoLineas = $this->promoLineas;
        $promoDescuento = (float) $this->promoDescuento;

        $compradorId = session('tienda_comprador_id');
        $compradorNombre = session('tienda_comprador_nombre');
        $compradorVendedor = session('tienda_comprador_vendedor');
        $compradorDescuento = session('tienda_comprador_descuento', 0);
        // Pedido armado por el preventista: queda a su nombre, con su comision
        $linkVendedor = session('tienda_link');
        if (!empty($linkVendedor['codven'])) {
            $compradorVendedor = $linkVendedor['codven'];
        }

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
        // Primera compra de un comprador registrado por la web: se crea como cliente de gestion
        if (!$compradorId && session('tienda_comprador_web_id')) {
            $compradores = new \App\Services\TiendaCompradorService($this->tablePrefix);
            $comprador = DB::connection('client_db')->table("{$this->tablePrefix}tienda_compradores")->where('id', session('tienda_comprador_web_id'))->first();
            if ($comprador) {
                $compradorId = $compradores->crearClienteGestion($comprador, $requiereDireccion ? $this->direccion : []);
                $compradores->iniciarSesion($compradores->porEmail($comprador->email));
                $compradorNombre = session('tienda_comprador_nombre');
            }
        }
        if (!$compradorId) {
            $this->pagoError = 'No pudimos identificar tu cuenta. Volvé a ingresar.';
            return;
        }

        $pagoRegistrado = [];
        $pedidoId = DB::connection('client_db')->transaction(function () use ($compradorId, $compradorNombre, $compradorVendedor, $vendedorNombre, $compradorDescuento, $resumenIva, $opcion, $entregaResumen, $requiereDireccion, $pagoMedio, $totalConEnvio, &$pagoRegistrado, $franjaTexto, $consumeCupo, $promoLineas, $promoDescuento) {

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
            'envio_transportista' => $opcion['transportista'] ?? null,
            'entrega_fecha'     => $this->agenda['activa'] ? $this->parafecha : null,
            'entrega_franja_id' => $this->agenda['activa'] && $this->entregaFranjaId ? (int) $this->entregaFranjaId : null,
            'entrega_franja'    => $franjaTexto ? mb_substr($franjaTexto, 0, 20) : null,
            'agenda_cupo'       => $consumeCupo ? 1 : 0,
            'descuento_promos'  => $promoDescuento,
            'promos_aplicadas'  => $promoLineas ? json_encode($promoLineas, JSON_UNESCAPED_UNICODE) : null,
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
            $this->pagoInfo['mp_url'] = $pagos->iniciarMercadoPago($pedidoId, (int) session('tienda_config_id'), $this->tiendaSlug, $items, (float) $this->envioImporte, $promoDescuento);
        }

        // Preparar datos para email antes de limpiar
        $itemsParaEmail = $this->carrito;
        $totalEmail = collect($this->carrito)->sum('subtotal');

        // Enviar emails
        $this->enviarEmails($pedidoId, $compradorNombre, $itemsParaEmail, $totalEmail, $entregaParaEmail, $envioParaEmail);

        // Link del preventista: usado, y el borrador del vendedor se reemplaza por este pedido
        if (!empty($linkVendedor['id'])) {
            (new \App\Services\TiendaLinkService($this->tablePrefix))->marcarUsado((int) $linkVendedor['id'], (int) $pedidoId);
            (new \App\Services\TiendaPedidoService($this->tablePrefix))->registrarEvento((int) $pedidoId, 'link_vendedor', 'Pedido armado por el vendedor ' . ($linkVendedor['vendedor'] ?: $linkVendedor['codven']) . ' y confirmado por el cliente', 'sistema');
            session()->forget('tienda_link');
            $this->linkVendedor = null;
        }

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
                'promos' => $this->promoLineas,
                'pedidoId' => $pedidoId,
                'clienteNombre' => $clienteNombre,
                'items' => $items,
                'total' => $total - (float) $this->promoDescuento + $envio,
                'subtotalProductos' => $total,
                'descuentoPromos' => (float) $this->promoDescuento,
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
            $this->ajustarPaso();
        }

        return view('tienda.carrito', [
            'total' => $this->getTotal(),
            'totalConEnvio' => $this->getTotalConPromos() + $this->envioImporte,
            'resumenIva' => \App\Services\TiendaPrecioService::resumenIva($this->carrito, $this->promoLineas),
            'googleMapsApiKey' => config('app.google_maps_api_key'),
        ]);
    }
}
