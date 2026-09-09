# Pedidos Web en gestión (Fivewin)

`PEDIDOSWEB.PRG` es el módulo de gestión que reemplaza la pestaña **Pedidos web** de Catálogo del panel. Está escrito con las mismas convenciones que `pedidos.prg` (Tdolphin, ventana MDI con barra de botones, recurso `ABMS`, `oApp`, `ClipValue2Sql`, `Auditar`, `ValidaError`, `AcepCanc`, `PintaBrw`, `Incrusta`).

**Todavía no está enganchado.** Se deja acá para compilarlo y ponerlo en marcha cuando la tienda esté en producción. Compilarlo antes de hacer los cambios de abajo no rompe nada, pero el módulo va a mostrar todos los pedidos web como "pasados" porque hoy la tienda los crea directamente en estado `P`.

## Qué hace

| Acción | Qué toca |
|---|---|
| Listado con filtros (situación, estado web, pago, búsqueda) | Lee `tienda_pedidos` + `pedidos_encab` + `tienda_compradores` |
| Detalle: artículos, entrega, pago, historial | `pedidos_det`, `tienda_eventos` |
| **Pasar a pedidos** | `pedidos_encab.estado` de `W` a `P`, `nuevo = 1`, evento `pasado_gestion` |
| Confirmar / rechazar pago | `tienda_pedidos.pago_estado`, evento `pago_aprobado` / `pago_rechazado` |
| Ver comprobante | Abre la URL de `tienda_pedidos.pago_comprobante` en el navegador |
| Cambiar estado web (con seguimiento) | `tienda_pedidos.estado_web`, `envio_seguimiento`, evento con `notificado = 0` |
| Generar envío en MiCorreo | POST al panel (ver más abajo), que llama a `TiendaCorreoService::importarPedido` |
| Pagos a cuenta de mayoristas | `tienda_pagos_cuenta.estado`, `confirmado`, `usuario`, `observa` |

Las reglas de estados web (flujo por modo de entrega, etiquetas) son una copia de `TiendaSeguimientoService`. Si cambian en el panel hay que cambiarlas también en el `.prg`.

## Para ponerlo en marcha

1. **Menú y permisos.** Agregar el módulo al menú de gestión (`menu_nuevo`, módulo `PEDWEB`) llamando a `PedidosWeb(cPermisos)`. Letras de permisos: `A` pasar a pedidos, `M` pagos / estados / envío, `E` exportar, `R` reporte.

2. **Estado `W` en gestión.** La idea es que el pedido web entre "en revisión" y no aparezca en Pedidos hasta que el comercio lo pase. Para eso:
   - En el panel, `app/Livewire/Tienda/Carrito.php` debe crear `pedidos_encab` con `estado = 'W'` en vez de `'P'`.
   - En `config/tienda.php`: agregar `'W' => 'Web en revisión'` a `estados_gestion` y `'W'` a `estados_comprometen_stock` (el pedido en revisión también reserva stock).
   - Revisar que la conciliación de comisiones (`TiendaComisionService`) trate `W` como pendiente.
   - La ventana Pedidos de gestión filtra `estado = 'P'`, así que los `W` quedan afuera sin tocar `pedidos.prg`.

3. **Endpoint del panel para MiCorreo.** El `.prg` hace:
   ```
   POST {URL_PANEL}/api/gestion/tienda/{cId}/pedidos/{idpedido}/envio-micorreo
   Header X-Gestion-Token: {token}
   Body {"usuario": "nombre del usuario de gestión"}
   Respuesta {"ok": true|false, "mensaje": "..."}
   ```
   Falta crearlo en `routes/api.php` (conecta la base del cliente con `TiendaService::conectarCliente` y llama a `TiendaCorreoService::importarPedido`). Ajustar `URL_PANEL` y `TOKEN_GESTION` al principio del `.prg`.

4. **Avisos al comprador.** Cuando el estado web cambia desde el panel, el panel manda el mail y el WhatsApp. Desde gestión queda el evento con `notificado = 0`; falta un comando del panel que los recorra y avise (o un endpoint como el de MiCorreo).

5. **Sacar la pestaña del panel.** Cuando el módulo esté andando, quitar la pestaña Pedidos web de Catálogo (`FotosProductos` y `resources/views/panel/fotos-productos.blade.php`).

## Codificación

El archivo está en Windows-1252, como el resto de las fuentes de `c:\fwh16\general\fuentes2`.
