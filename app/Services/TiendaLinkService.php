<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Link del preventista (F6): el vendedor arma el pedido en su modulo, manda un
 * link a su cliente, el cliente lo revisa en la tienda y confirma. El pedido
 * queda a nombre del vendedor (codven) con su comision, y el borrador del
 * vendedor se reemplaza por el pedido confirmado.
 *
 * Vive en tienda_links: token, borrador de origen, cliente, vendedor y los
 * renglones al momento de generarlo. Los precios se resuelven cuando el cliente
 * entra (con su lista), no cuando el vendedor manda el link.
 */
class TiendaLinkService
{
    public const DIAS_VIGENCIA = 7;

    private string $prefijo;

    public function __construct(string $prefijo)
    {
        $this->prefijo = $prefijo;
    }

    private function tabla(string $nombre)
    {
        return DB::connection('client_db')->table($this->prefijo . $nombre);
    }

    /**
     * Crea el link a partir del borrador del vendedor (pedidos_encab en armado).
     * Devuelve ['ok', 'mensaje', 'token', 'link'].
     */
    public function crearDesdePedido(int $idpedido, ?string $codven, ?string $vendedorNombre): array
    {
        $encab = DB::connection('client_db')->table($this->prefijo . 'pedidos_encab')->where('id', $idpedido)->first();
        if (!$encab) {
            return ['ok' => false, 'mensaje' => 'No encontré el pedido.'];
        }
        $renglones = DB::connection('client_db')->table($this->prefijo . 'pedidos_det')->where('idpedido', $idpedido)
            ->get(['codart', 'detart', 'cantidad', 'punitario', 'descu']);
        if ($renglones->isEmpty()) {
            return ['ok' => false, 'mensaje' => 'El pedido no tiene artículos todavía.'];
        }

        // Un link vigente por borrador: si ya hay uno, se devuelve el mismo
        $vigente = $this->tabla('tienda_links')->where('idpedido_borrador', $idpedido)->whereNull('usado')
            ->where('vence', '>', now())->orderByDesc('id')->first();
        if ($vigente) {
            return ['ok' => true, 'mensaje' => 'Este pedido ya tiene un link vigente.', 'token' => $vigente->token, 'link' => $vigente];
        }

        $token = Str::random(40);
        $id = $this->tabla('tienda_links')->insertGetId([
            'token'             => $token,
            'idpedido_borrador' => $idpedido,
            'codcli'            => (int) $encab->codcli,
            'codven'            => $codven ?: ($encab->codven ?: null),
            'vendedor'          => $vendedorNombre ?: ($encab->vendedor ?: null),
            'items'             => json_encode($renglones->map(fn ($r) => ['codigo' => $r->codart, 'nombre' => $r->detart, 'cantidad' => (float) $r->cantidad])->all(), JSON_UNESCAPED_UNICODE),
            'observa'           => $encab->observa ?: null,
            'creado'            => now(),
            'vence'             => now()->addDays(self::DIAS_VIGENCIA),
        ]);

        return ['ok' => true, 'mensaje' => 'Link generado.', 'token' => $token, 'link' => $this->tabla('tienda_links')->where('id', $id)->first()];
    }

    /** Link por token, solo si sigue vigente y sin usar. Devuelve ['link' => obj|null, 'motivo' => string|null]. */
    public function porToken(?string $token): array
    {
        $token = trim((string) $token);
        if ($token === '' || strlen($token) > 64) {
            return ['link' => null, 'motivo' => 'El link no es válido.'];
        }
        $link = $this->tabla('tienda_links')->where('token', $token)->first();
        if (!$link) {
            return ['link' => null, 'motivo' => 'El link no es válido.'];
        }
        if ($link->usado) {
            return ['link' => null, 'motivo' => 'Este pedido ya fue confirmado. Lo ves en Mis pedidos.'];
        }
        if ($link->vence < now()->toDateTimeString()) {
            return ['link' => null, 'motivo' => 'El link venció. Pedile a tu vendedor que te mande uno nuevo.'];
        }

        return ['link' => $link, 'motivo' => null];
    }

    public function registrarApertura(int $id): void
    {
        $this->tabla('tienda_links')->where('id', $id)->increment('veces_abierto', 1, ['ultima_apertura' => now()]);
    }

    /**
     * Arma el carrito de la tienda con los renglones del link, a precio de hoy y de la
     * lista del comprador logueado (mismo criterio que el catalogo). Lo que dejo de
     * venderse o esta oculto se saltea y se informa.
     * @return array{carrito: array, salteados: array}
     */
    public function armarCarrito(object $link, string $modo): array
    {
        $items = json_decode($link->items, true) ?: [];
        $codigos = array_map(fn ($i) => (string) $i['codigo'], $items);
        if (empty($codigos)) {
            return ['carrito' => [], 'salteados' => []];
        }

        $productos = DB::connection('client_db')
            ->table("{$this->prefijo}articu as a")
            ->leftJoin("{$this->prefijo}articu_fotos as f", 'a.CODIGO', '=', 'f.codigo')
            ->leftJoin("{$this->prefijo}ivas as i", 'a.IVA', '=', 'i.codigo')
            ->leftJoin("{$this->prefijo}tienda_articu as ta", 'a.CODIGO', '=', 'ta.codigo')
            ->whereIn('a.CODIGO', $codigos)
            ->where('a.NOSALE', 0)->whereRaw('COALESCE(ta.visible_web, 1) = 1')
            ->get(['a.CODIGO', 'a.NOMBRE', 'a.PRECIOVEN', 'a.REVENTA', 'a.STOCKACT', 'f.url as foto_url', 'i.tasa as tasa_iva'])
            ->keyBy(fn ($p) => (string) $p->CODIGO);

        $lispreesp = session('tienda_comprador_lispreesp');
        $lispre = session('tienda_comprador_lispre');
        $usaLista = TiendaService::esPrivada($modo) && session('tienda_comprador_id');
        $especiales = ($usaLista && $lispreesp)
            ? DB::connection('client_db')->table("{$this->prefijo}lispredet")->where('CODLIS', $lispreesp)->whereIn('CODART', $codigos)->pluck('PRECIO', 'CODART')
            : collect();

        $carrito = [];
        $salteados = [];
        foreach ($items as $it) {
            $p = $productos[(string) $it['codigo']] ?? null;
            if (!$p) {
                $salteados[] = $it['nombre'] ?? $it['codigo'];
                continue;
            }
            $precio = (float) $p->PRECIOVEN;
            if ($usaLista) {
                $precio = isset($especiales[$p->CODIGO]) ? (float) $especiales[$p->CODIGO] : ($lispre == 2 ? (float) $p->REVENTA : (float) $p->PRECIOVEN);
            }
            $cant = (float) $it['cantidad'];
            $carrito[] = [
                'codigo'     => $p->CODIGO,
                'nombre'     => $p->NOMBRE,
                'precio'     => $precio,
                'precio_ven' => $p->PRECIOVEN,
                'precio_rev' => $p->REVENTA,
                'cantidad'   => $cant == floor($cant) ? (int) $cant : $cant,
                'subtotal'   => $precio * $cant,
                'foto_url'   => $p->foto_url,
                'tasa'       => (float) ($p->tasa_iva ?? 0),
            ];
        }

        return ['carrito' => $carrito, 'salteados' => $salteados];
    }

    /** El cliente confirmo: se marca usado, se anota el pedido final y se borra el borrador del vendedor si sigue en armado. */
    public function marcarUsado(int $id, int $idpedidoFinal): void
    {
        $link = $this->tabla('tienda_links')->where('id', $id)->first();
        if (!$link) {
            return;
        }
        $this->tabla('tienda_links')->where('id', $id)->update(['usado' => now(), 'idpedido_final' => $idpedidoFinal]);

        $borrador = DB::connection('client_db')->table($this->prefijo . 'pedidos_encab')->where('id', $link->idpedido_borrador)->first(['id', 'estado']);
        if ($borrador && $borrador->estado === TiendaEstadoPedido::EN_ARMADO) {
            DB::connection('client_db')->table($this->prefijo . 'pedidos_det')->where('idpedido', $borrador->id)->delete();
            DB::connection('client_db')->table($this->prefijo . 'pedidos_encab')->where('id', $borrador->id)->delete();
        }
    }

    /** Links del vendedor (para ver cuales abrio o confirmo el cliente). */
    public function delVendedor(?string $codven, int $limite = 30): array
    {
        return $this->tabla('tienda_links as l')
            ->leftJoin($this->prefijo . 'clientes as c', 'c.CODIGO', '=', 'l.codcli')
            ->when($codven, fn ($q) => $q->where('l.codven', $codven))
            ->orderByDesc('l.id')->limit($limite)
            ->get(['l.*', 'c.NOMBRE as cliente'])->map(fn ($r) => (array) $r)->all();
    }

    public static function url(string $slug, string $token): string
    {
        return route('tienda.link', ['tienda' => $slug, 'token' => $token]);
    }

    public static function urlWhatsapp(?string $telefono, string $texto): ?string
    {
        $tel = preg_replace('/\D/', '', (string) $telefono);
        if ($tel === '') {
            return null;
        }
        if (strlen($tel) <= 11 && !str_starts_with($tel, '54')) {
            $tel = '54' . ltrim($tel, '0');   // Argentina, sin el 0 de area
        }

        return 'https://wa.me/' . $tel . '?text=' . rawurlencode($texto);
    }
}
