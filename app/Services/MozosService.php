<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;

class MozosService
{
    protected $tablePrefix;
    protected $clientId;

    public function __construct()
    {
        $this->clientId = session('client_id');
        $this->setupClientDatabase();
    }

    private function setupClientDatabase()
    {
        if ($this->clientId) {
            $cliente = Cliente::find($this->clientId);
            if ($cliente) {
                Config::set('database.connections.client_db.database', $cliente->base);
                DB::purge('client_db');
                $this->tablePrefix = $cliente->getTablePrefix();
            }
        }
    }

    public function obtenerMesas($salon)
    {
        $sql = "SELECT res.total, res.items, res.mesa, res.mozo, res.recurso, res.salon, res.estado FROM
                  (SELECT SUM(d.total) as total, COUNT(d.renglon) AS items, d.mesa, m.mozo, m.recurso, m.salon, SUM(IF(d.estado=3,1,0)) as estado
                  FROM {$this->tablePrefix}detalle d LEFT JOIN {$this->tablePrefix}mesas m ON d.mesa = m.numero
                  GROUP BY d.mesa, m.mozo, m.recurso, m.salon
                  UNION
                  SELECT 0 as total, 0 as renglon, numero as mesa, mozo, recurso, salon, 0 as estado
                  FROM {$this->tablePrefix}mesas WHERE numero NOT IN (SELECT DISTINCT mesa FROM {$this->tablePrefix}detalle)
                ) res
                WHERE res.salon = ?
                ORDER BY res.mesa";

        return DB::connection('client_db')->select($sql, [$salon]);
    }

    public function obtenerSalones()
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'salones')
                 ->get();
    }

    public function obtenerDetalleMesa($numeroMesa)
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'detalle')
                 ->where('mesa', $numeroMesa)
                 ->orderBy('renglon')
                 ->get();
    }

    public function obtenerInfoMesa($numeroMesa)
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'mesas as m')
                 ->select('m.NUMERO as numero', 'm.RECURSO as recurso',
                          'm.MOZO as mozo', 'm.COMENSALES as comensales',
                          'm.OCUPADA as ocupada', 'm.usando as usando',
                          'm.fechaaper as fechaaper', 'm.horaaper as horaaper',
                          'm.unificada as unificada', 'm.salon as salon',
                          'mo.NOMBRE as nombre_mozo')
                 ->leftJoin($this->tablePrefix . 'mozos as mo', 'mo.codigo', '=', 'm.MOZO')
                 ->where('m.NUMERO', $numeroMesa)
                 ->first();
    }

    public function hayTurnoAbierto()
    {
        $turnos = DB::connection('client_db')
                    ->table($this->tablePrefix . 'fondo')
                    ->where('checkeado', false)
                    ->count();
        return $turnos > 0;
    }

    public function obtenerParametros()
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'parametros')
                 ->select('actualizarmesas as pedircomen', 'gastosman as precioxcub')
                 ->first();
    }

    public function agregarProducto($data)
    {
        DB::connection('client_db')->beginTransaction();
        try {
            $articu = DB::connection('client_db')
                        ->table($this->tablePrefix . 'articu as a')
                        ->select('a.IVA as iva', DB::RAW('IF(a.precio_oferta > 0, a.precio_oferta, a.PRECIO) as precio'),
                                'i.tasa AS tasa', 'a.NOMBRE AS nombre', 'a.lMesas as habilitado_mesas',
                                'a.agotado', 'a.CODIGO as codigo_art')
                        ->leftJoin($this->tablePrefix . 'ivas as i', 'a.IVA', '=', 'i.codigo')
                        ->where('a.CODIGO', $data['codigo'])
                        ->first();

            if (!$articu) {
                \Log::error('Artículo no encontrado', ['codigo' => $data['codigo'], 'prefix' => $this->tablePrefix]);
                throw new \Exception('Artículo no encontrado (código: ' . $data['codigo'] . ')');
            }

            if (!$articu->habilitado_mesas) {
                \Log::error('Artículo no habilitado para mesas', ['codigo' => $data['codigo'], 'articulo' => $articu]);
                throw new \Exception('El artículo ' . $data['codigo'] . ' no está habilitado para mesas. Debe activar la opción "lMesas" en la configuración del artículo.');
            }

            if ($articu->agotado) {
                throw new \Exception('El artículo "' . $articu->nombre . '" está marcado como agotado');
            }

            $tasa = $articu->tasa ?? 0;
            $total = $articu->precio * $data['cantidad'];
            $neto = $tasa > 0 ? $total / (1 + $tasa / 100) : $total;
            $iva = $total - $neto;

            \Log::info('Agregando artículo', [
                'codigo' => $data['codigo'],
                'nombre' => $articu->nombre,
                'precio' => $articu->precio,
                'tasa' => $tasa,
                'total' => $total,
                'mesa' => $data['mesa']
            ]);

            $renglon = $this->obtenerProximoRenglon($data['mesa']);
            $esApertura = ($renglon == 1);

            // Si es apertura de mesa, abrir primero
            if ($esApertura) {
                $this->abrirMesa($data['mesa'], $data['comensales']);
                // Recalcular renglón después de abrir (puede haber agregado servicio de mesa)
                $renglon = $this->obtenerProximoRenglon($data['mesa']);
            }

            // Truncar nombre si es muy largo (máximo 27 caracteres para dejar espacio a "(w)")
            $nombreTruncado = mb_strlen($articu->nombre) > 27
                ? mb_substr($articu->nombre, 0, 27)
                : $articu->nombre;

            DB::connection('client_db')
              ->table($this->tablePrefix . 'detalle')
              ->insert([
                  'MESA' => $data['mesa'],
                  'RENGLON' => $renglon,
                  'CODART' => $data['codigo'],
                  'NOMART' => $nombreTruncado . '(w)',
                  'CANTIDAD' => $data['cantidad'],
                  'PUNITARIO' => $articu->precio,
                  'NETO' => $neto,
                  'IVA' => $iva,
                  'TOTAL' => $total,
                  'guarnicion' => ' ',
                  'caracteristicas' => $data['caracteristicas'] ?? ' ',
                  'CODIVA' => $articu->iva,
                  'sabores' => ' ',
                  'estado' => 1,
                  'hora' => DB::raw('CURTIME()'),
                  'IMPRESA' => false
              ]);

            $this->actualizarPuntos($data['mesa']);

            DB::connection('client_db')->commit();
            return true;
        } catch (\Exception $e) {
            DB::connection('client_db')->rollBack();
            \Log::error('Error al agregar producto', [
                'codigo' => $data['codigo'] ?? null,
                'mesa' => $data['mesa'] ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    private function obtenerProximoRenglon($mesa)
    {
        $maxRenglon = DB::connection('client_db')
                        ->table($this->tablePrefix . 'detalle')
                        ->where('mesa', $mesa)
                        ->max('renglon');

        return $maxRenglon ? $maxRenglon + 1 : 1;
    }

    public function abrirMesa($numeroMesa, $comensales)
    {
        // Obtener parámetros
        $param = $this->obtenerParametros();

        // Validar comensales si cobra cubierto Y los comensales son obligatorios
        if ($param && $param->pedircomen && $param->precioxcub > 0) {
            if ($comensales < 1 || $comensales > 25) {
                throw new \Exception('Debe ingresar la cantidad de comensales (entre 1 y 25)');
            }
        }

        DB::connection('client_db')
          ->table($this->tablePrefix . 'mesas')
          ->where('NUMERO', $numeroMesa)
          ->update([
              'COMENSALES' => $comensales,
              'RECURSO' => DB::raw("IF(LEFT(recurso,1)='N',CONCAT(LEFT(recurso,6),'0'),CONCAT(LEFT(recurso,5),'0'))"),
              'ocupada' => true,
              'MOZO' => session('mozo_user_id'),
              'fechaaper' => DB::raw('CURDATE()'),
              'horaaper' => DB::raw('CURTIME()')
          ]);

        $this->registrarAuditoria(1, 'APERTURA DE MESA ' . $numeroMesa . ' (W)', $numeroMesa);

        // Si cobra cubierto, agregar servicio de mesa
        if ($param && $param->precioxcub > 0 && $comensales > 0) {
            $this->agregarServicioMesa($numeroMesa, $comensales, $param->precioxcub);
        }
    }

    private function agregarServicioMesa($numeroMesa, $comensales, $precioxcub)
    {
        $total = $precioxcub * $comensales;
        $neto = $total / 1.21;
        $iva = $total - $neto;

        DB::connection('client_db')
          ->table($this->tablePrefix . 'detalle')
          ->insert([
              'MESA' => $numeroMesa,
              'RENGLON' => 1,
              'CODART' => -1,
              'NOMART' => 'SERVICIO DE MESA (w)',
              'CANTIDAD' => $comensales,
              'PUNITARIO' => $precioxcub,
              'NETO' => $neto,
              'IVA' => $iva,
              'TOTAL' => $total,
              'guarnicion' => ' ',
              'caracteristicas' => ' ',
              'CODIVA' => 5,
              'sabores' => ' ',
              'estado' => 1,
              'hora' => DB::raw('CURTIME()'),
              'IMPRESA' => false,
              'OBSERVA' => ''
          ]);
    }

    public function eliminarProducto($mesa, $renglon)
    {
        DB::connection('client_db')->beginTransaction();
        try {
            $producto = DB::connection('client_db')
                          ->table($this->tablePrefix . 'detalle')
                          ->where('RENGLON', $renglon)
                          ->where('MESA', $mesa)
                          ->first();

            if (!$producto) {
                throw new \Exception('Producto no encontrado');
            }

            // No permitir borrar servicio de mesa
            if (strpos(strtoupper($producto->NOMART), 'SERVICIO DE MESA') === 0) {
                throw new \Exception('No se puede eliminar el servicio de mesa');
            }

            // Validar permisos
            if ($producto->IMPRESA && !session('mozo_borracc')) {
                throw new \Exception('No tiene permisos para borrar con comanda');
            }
            if (!$producto->IMPRESA && !session('mozo_borrasc')) {
                throw new \Exception('No tiene permisos para borrar sin comanda');
            }

            DB::connection('client_db')
              ->table($this->tablePrefix . 'detalle')
              ->where('RENGLON', $renglon)
              ->where('MESA', $mesa)
              ->delete();

            $detalle = ($producto->IMPRESA ? 'CC:' : 'SC:') . $producto->CODART . ' ' . $producto->NOMART . ' (' . $producto->CANTIDAD . ')';
            $this->registrarAuditoria(4, 'BORRADO DET. EN MESA ' . $mesa . ' (W) ' . $detalle, $mesa);

            // Si no quedan productos, cancelar mesa
            $restantes = DB::connection('client_db')
                           ->table($this->tablePrefix . 'detalle')
                           ->where('MESA', $mesa)
                           ->count();

            if ($restantes == 0) {
                $this->cancelarMesa($mesa);
            }

            $this->actualizarPuntos($mesa);

            DB::connection('client_db')->commit();
            return true;
        } catch (\Exception $e) {
            DB::connection('client_db')->rollBack();
            throw $e;
        }
    }

    public function actualizarServicioMesa($numeroMesa, $nuevosComensales)
    {
        // Obtener parámetros
        $param = $this->obtenerParametros();

        // Solo actualizar si cobra cubierto
        if (!$param || $param->precioxcub <= 0) {
            return;
        }

        // Validar que no se pueda poner comensales en 0 si cobra cubierto
        if ($nuevosComensales < 1) {
            throw new \Exception('No se puede reducir los comensales a 0 cuando cobra cubierto');
        }

        // Buscar el servicio de mesa en el detalle
        $servicioMesa = DB::connection('client_db')
                          ->table($this->tablePrefix . 'detalle')
                          ->where('MESA', $numeroMesa)
                          ->where('CODART', -1)
                          ->whereRaw("UPPER(NOMART) LIKE 'SERVICIO DE MESA%'")
                          ->first();

        if ($servicioMesa) {
            // Actualizar el servicio con los nuevos comensales
            $total = $param->precioxcub * $nuevosComensales;
            $neto = $total / 1.21;
            $iva = $total - $neto;

            DB::connection('client_db')
              ->table($this->tablePrefix . 'detalle')
              ->where('MESA', $numeroMesa)
              ->where('RENGLON', $servicioMesa->RENGLON)
              ->update([
                  'CANTIDAD' => $nuevosComensales,
                  'NETO' => $neto,
                  'IVA' => $iva,
                  'TOTAL' => $total
              ]);

            // Actualizar puntos
            $this->actualizarPuntos($numeroMesa);
        } else {
            // Si no existe el servicio pero hay comensales, agregarlo
            $this->agregarServicioMesa($numeroMesa, $nuevosComensales, $param->precioxcub);
            $this->actualizarPuntos($numeroMesa);
        }
    }

    public function cancelarMesa($numeroMesa)
    {
        // Eliminar todo el detalle de la mesa (incluyendo servicio de mesa si existe)
        DB::connection('client_db')
          ->table($this->tablePrefix . 'detalle')
          ->where('MESA', $numeroMesa)
          ->delete();

        // Resetear la mesa a estado libre
        DB::connection('client_db')
          ->table($this->tablePrefix . 'mesas')
          ->where('NUMERO', $numeroMesa)
          ->update([
              'COMENSALES' => 0,
              'RECURSO' => DB::raw("LEFT(recurso, 5)"),
              'codigo_cd' => ' ',
              'color' => 16777215,
              'cliente' => 1,
              'descuento' => 0,
              'coddes' => 0,
              'usando' => 0,
              'lResumido' => false,
              'cResumido' => 'CENA',
              'ocupada' => false
          ]);

        $this->registrarAuditoria(5, 'CANCELACION DE MESA ' . $numeroMesa . ' (W)', $numeroMesa);
        $this->actualizarPuntos($numeroMesa);
    }

    public function enviarComanda($numeroMesa)
    {
        DB::connection('client_db')
          ->table($this->tablePrefix . 'mesas')
          ->where('NUMERO', $numeroMesa)
          ->update(['comandaweb' => true]);

        $this->actualizarPuntos($numeroMesa);
    }

    public function enviarPrecuenta($numeroMesa)
    {
        DB::connection('client_db')
          ->table($this->tablePrefix . 'mesas')
          ->where('NUMERO', $numeroMesa)
          ->update([
              'precuentaweb' => true,
              'RECURSO' => DB::raw("IF(LEFT(recurso,1)='N',CONCAT(LEFT(recurso,6),'1'),CONCAT(LEFT(recurso,5),'1'))")
          ]);

        DB::connection('client_db')
          ->table($this->tablePrefix . 'punto')
          ->update(['actualizarmesas' => true]);

        $this->actualizarPuntos($numeroMesa);
    }

    private function actualizarPuntos($mesa)
    {
        DB::connection('client_db')
          ->statement("INSERT INTO {$this->tablePrefix}actualizar (punto, mesa)
                       (SELECT ip, ? FROM {$this->tablePrefix}punto)", [$mesa]);
    }

    private function registrarAuditoria($tipo, $descripcion, $mesa)
    {
        DB::connection('client_db')
          ->table($this->tablePrefix . 'auditoria')
          ->insert([
              'TIPO' => $tipo,
              'DESCRIPCION' => $descripcion,
              'FECHA' => DB::raw('CURDATE()'),
              'HORA' => DB::raw('CURTIME()'),
              'USUARIO' => session('mozo_user'),
              'MESA' => $mesa,
          ]);
    }

    public function buscarArticulos($busqueda)
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'articu')
                 ->select('CODIGO as codigo', 'NOMBRE as nombre',
                          DB::raw('IF(precio_oferta > 0, precio_oferta, PRECIO) as precio'),
                          'agotado', 'stock', 'lstock')
                 ->where('lMesas', true)
                 ->where(function($q) use ($busqueda) {
                     $q->where('NOMBRE', 'like', '%' . $busqueda . '%')
                       ->orWhere('CODIGO', 'like', '%' . $busqueda . '%');
                 })
                 ->limit(20)
                 ->get();
    }

    public function obtenerArticulo($codigo)
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'articu')
                 ->select('CODIGO as codigo', 'NOMBRE as nombre',
                          DB::raw('IF(precio_oferta > 0, precio_oferta, PRECIO) as precio'),
                          'observa_web as observa', 'agotado', 'lstock', 'stock', 'solo_unitario')
                 ->where('CODIGO', $codigo)
                 ->first();
    }

    public function obtenerOpcionalesArticulo($codigoArticulo)
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'articulos_opcionales AS o')
                 ->select('o.id AS idgrupo', 'od.id AS iddet', 'o.obligatorio AS obligatorio',
                         'o.nombre AS nomgru', 'od.precio AS precio_opc',
                         'o.minimo AS minimo', 'o.maximo AS maximo', 'o.por_cantidad AS por_cantidad',
                         'od.nombre AS nomopc', 'od.agotado AS agotado_opc')
                 ->leftJoin($this->tablePrefix . 'articulos_opcionales_art AS od', function($q) use ($codigoArticulo) {
                     $q->on('od.idgrupo', '=', 'o.id')
                       ->where('od.idarticulo', '=', $codigoArticulo);
                 })
                 ->where('o.idproducto', $codigoArticulo)
                 ->get();
    }

    public function tieneOpcionales($codigoArticulo)
    {
        return DB::connection('client_db')
                 ->table($this->tablePrefix . 'articulos_opcionales')
                 ->where('idproducto', $codigoArticulo)
                 ->exists();
    }
}
