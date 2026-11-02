# Subida del Catálogo (tienda PyMEs) a producción: clientes 1 y 2409

Punto de partida: las tablas propias de `ge_000001` y `ge_002409` ya se copiaron de local a
producción, y las fotos ya están en Cloudflare R2.

⚠ = archivo **compartido** con otros módulos. Compararlo con la versión del servidor antes de
pisarlo (WinMerge), porque el servidor tiene cambios que no están en GitHub.
El resto son archivos solo de la tienda y se suben directo.

## 1) Archivos a subir

```
app/Console/Commands/
  TiendaAvisosPendientes.php
  TiendaCarritosAbandonados.php
  TiendaComisionesInforme.php
  TiendaConciliarComisiones.php
  TiendaDominios.php
  TiendaImportarCompradores.php
  TiendaImportarFotos.php

app/Http/Controllers/
  MercadoPagoController.php            ⚠ webhook y consulta de pago de la tienda
  TiendaController.php

app/Http/Controllers/Api/
  TiendaGestionController.php

app/Http/Middleware/
  TiendaDominioMiddleware.php
  TiendaMiddleware.php

app/Livewire/Panel/
  ConfigurarAgenda.php
  ConfigurarEnvios.php
  ConfigurarIntegraciones.php
  ConfigurarPagos.php
  FotosProductos.php
  Inicio.php                           ⚠ tarjeta "Tienda web: estado de cuenta"
  PedidosWeb.php

app/Livewire/Tienda/
  Carrito.php
  Catalogo.php
  MiCuenta.php
  MisPedidos.php

app/Livewire/Vendedores/
  GestorPedidosVendedor.php            ⚠ botón "Link para el cliente"

app/Services/
  CorreoArgentinoException.php
  CorreoArgentinoService.php
  ImagenProductoService.php
  MercadoPagoService.php               ⚠ crearPreferenciaTienda
  TiendaAgendaService.php
  TiendaAsistenteIaService.php
  TiendaCarritoService.php
  TiendaComisionService.php
  TiendaCompradorService.php
  TiendaCorreoService.php
  TiendaCuentaService.php
  TiendaDominioService.php
  TiendaEnvioService.php
  TiendaEstadoPedido.php
  TiendaEstiloService.php
  TiendaFacturaService.php
  TiendaFavoritoService.php
  TiendaFotoService.php
  TiendaIaService.php
  TiendaIntegracionService.php
  TiendaLinkService.php
  TiendaMetricaService.php
  TiendaPagoService.php
  TiendaPedidoService.php
  TiendaPrecioService.php
  TiendaPromoService.php
  TiendaSeguimientoService.php
  TiendaSeoService.php
  TiendaService.php
  TiendaStockService.php
  TiendaWhatsappService.php

bootstrap/
  app.php                              ⚠ $middleware->prepend(TiendaDominioMiddleware::class)

config/
  app.php                              ⚠ timezone UTC -> America/Argentina/Buenos_Aires (afecta TODO el panel)
  correo.php
  filesystems.php                      ⚠ disco r2
  services.php                         ⚠ google_maps_api_key, google_maps_server_key, anthropic
  tienda.php

public/build/                          (carpeta entera, después de npm run build en local)

public/img/
  arca.jpg

public/img/logos/1/
  logo.png
  portada.png
  sin-foto.png

public/img/logos/2409/
  logo.png
  portada.png
  sin-foto.png

resources/css/
  app.css                              ⚠ clases .t-* (se compila a public/build)

resources/views/components/
  subir-archivo.blade.php

resources/views/emails/
  pedido.blade.php                     ⚠
  tienda-cuenta.blade.php
  tienda-pedido-estado.blade.php

resources/views/layouts/
  tienda.blade.php

resources/views/panel/
  configurar-agenda.blade.php
  configurar-envios.blade.php
  configurar-integraciones.blade.php
  configurar-pagos.blade.php
  dashboard.blade.php                  ⚠ ítem Catálogo del menú
  fotos-productos.blade.php
  inicio.blade.php                     ⚠ tarjeta de comisiones
  pedidos-web.blade.php
  tienda-no-contratada.blade.php

resources/views/panel/partials/
  comisiones-tienda.blade.php

resources/views/tienda/
  arrepentimiento.blade.php
  carrito.blade.php
  catalogo.blade.php
  factura.blade.php
  legales.blade.php
  login.blade.php
  mensaje.blade.php
  mi-cuenta.blade.php
  mis-pedidos.blade.php
  no-disponible.blade.php
  nueva-clave.blade.php
  pago.blade.php
  recuperar.blade.php
  registro.blade.php
  (borrar comprobante.blade.php si está: ya no se usa)

resources/views/tienda/partials/
  active-filters.blade.php
  cuenta-cabecera.blade.php
  datos-transferencia.blade.php
  estilo.blade.php
  filtros-drawer.blade.php
  product-grid.blade.php
  product-info.blade.php
  sidebar-filters.blade.php
  tarjeta-mini.blade.php

resources/views/vendedores/
  gestor-pedido-vendedor.blade.php     ⚠

routes/
  console.php                          ⚠ tienda:avisos-pendientes y tienda:carritos-abandonados
  web.php                              ⚠ rutas /web/{tienda}/..., MP de la tienda, /api/gestion/tienda/...

(raíz)
  composer.json                        ⚠ anthropic-ai/sdk, barryvdh/laravel-dompdf,
  composer.lock                        ⚠ chillerlan/php-qrcode, league/flysystem-aws-s3-v3
```

## 2) Configuración en producción

**Paquetes y compilado (subida por SFTP, todo se prepara en local)**
- [ ] `npm run build` en local y subir `public/build/` entera (borrar antes los `assets/` viejos
      del servidor o pisar todo; lo que manda es `manifest.json`).
- [ ] `composer.json` y `composer.lock` nuevos → subir **`vendor/` entera** desde local (los
      paquetes nuevos traen dependencias, por ejemplo aws-sdk-php para R2, y `vendor/composer/`
      tiene el autoload con todo). Antes, confirmar que el PHP local y el de producción sean de
      la misma versión mayor (`php -v` en los dos).
- [ ] Limpiar cachés: sin consola, borrar por SFTP `bootstrap/cache/config.php`,
      `bootstrap/cache/routes-v7.php`, `bootstrap/cache/packages.php`,
      `bootstrap/cache/services.php` (si existen) y el contenido de `storage/framework/views/`.

**`.env`**
```
TIENDA_CLIENTES_HABILITADOS=1,2409
TIENDA_OFRECER_SERVICIO=false
TIENDA_PEDIDOS_EN_REVISION=false     # los pedidos web entran a gestión como P
TIENDA_GESTION_TOKEN=                # vacío hasta enganchar PEDIDOSWEB.PRG

R2_ACCESS_KEY_ID=                    # copiar del .env local
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=
R2_URL=
IMAGENES_PRODUCTOS_DISK=r2

GOOGLE_MAPS_API_KEY=                 # autorizar el dominio real del panel en Google Cloud
GOOGLE_MAPS_SERVER_KEY=              # opcional, restringida a la IP del servidor

CACHE_STORE=database
ANTHROPIC_API_KEY=                   # opcional
```
- [ ] `MAIL_*` funcionando (registro, recuperar clave, avisos de estado).
- [ ] Después de tocar el `.env`: volver a borrar `bootstrap/cache/config.php`.

**Cron**
- [ ] `* * * * * cd /ruta/del/panel && php artisan schedule:run >> /dev/null 2>&1`

## 3) Datos de tablas existentes (clientes 1 y 2409)

**`bcn_config.config_clientes`**
- [ ] `tienda` (slug de `/web/{slug}`) igual que en local.
- [ ] `comision` y `lComision`: la tienda cobra ese % sobre cada pedido si `lComision = 1`.
      En local al cliente 1 se le pusieron 5% / 1 de prueba. Dejar el valor real que corresponda.

**`bcn_config.config_clientes_web`** (columna `configuracion`, por `cliente_id`)
- [ ] Copiar el JSON de local para 1 y 2409. Ahí está todo lo de "Mi Tienda" (modo, colores,
      portada, fuentes, orden, legales, dominio) y `"tienda_esquema_version": 1`.
      Si el cliente ya tenía fila en producción (lo usa también el kiosco de resto), **mezclar**
      las claves `tienda_*` / de la tienda, no reemplazar el JSON entero.

**Limpiar datos de prueba** que vinieron con la copia de tablas (en `ge_000001` y `ge_002409`):
```sql
-- apuntan a pedidos_encab de LOCAL: en producción esos idpedido son otros pedidos
TRUNCATE {p}tienda_pedidos;
TRUNCATE {p}tienda_eventos;
TRUNCATE {p}tienda_pagos_cuenta;
TRUNCATE {p}tienda_carritos;
TRUNCATE {p}tienda_links;
TRUNCATE {p}tienda_metricas;
TRUNCATE {p}tienda_favoritos;
-- compradores: se vuelven a importar en producción (ver abajo)
TRUNCATE {p}tienda_compradores;
TRUNCATE {p}tienda_direcciones;
```
- [ ] `tienda_integraciones`: está cifrada con el `APP_KEY` local. Si el de producción es
      distinto, no rompe pero se ve vacía: volver a cargar MP / correo / IA / WhatsApp desde
      Catálogo → Pagos / Integraciones. Si es el mismo `APP_KEY`, revisar que no quede el
      token falso de MP del cliente 1 (`APP_USR-token-de-prueba-123`).
- [ ] Cliente 1: en local se cargaron promos `TEST ...` en `promociones` y tablas `promo_grupo*`.
      Si se copiaron, borrarlas.

**Compradores**
- [ ] `php artisan tienda:importar-compradores 2409 --dry-run`, después sin `--dry-run`.
      Guardar la salida (contraseña de cada comprador). Necesita consola en el servidor; si no
      hay, se puede correr **desde local apuntando a la base de producción** (cambiar
      temporalmente `DB_HOST`/usuario/clave en el `.env` local), porque solo escribe en la base
      del cliente.
- [ ] Cron (`schedule:run` cada minuto): también necesita acceso al servidor (consola o panel
      de hosting). Sin cron, no salen los avisos de estado hechos desde gestión ni los de
      carrito abandonado; la tienda igual funciona.

## 4) Otras cosas

- [ ] **Pedidos en gestión:** con `TIENDA_PEDIDOS_EN_REVISION=false` entran como `P` y se ven
      en Pedidos. El módulo `PEDIDOSWEB.PRG` queda para después (ver `fivewin/README.md`);
      mientras tanto, pagos y estados web se manejan desde Catálogo → Pedidos web del panel.
- [ ] `tienda:conciliar-comisiones` no está programado: sumarlo a `routes/console.php` o al cron.
- [ ] Mercado Pago: en producción hay https, así que el webhook `/mp/webhook/{clientId}` sí
      llega. Probar un pago real chico.
- [ ] Correo Argentino: nunca se probó con cuenta real. No activarlo hasta probar desde
      Integraciones → Probar.
- [ ] Dominio propio: no usar todavía (necesita Caddy, ver `dominio-propio.md`).

**Prueba final**
- [ ] `/web/{slug}` del 2409: catálogo con fotos.
- [ ] Entrar al panel con otro cliente: no tiene que ver Catálogo, y su `/web/...` tiene que decir "no disponible".
- [ ] Pedido de prueba → aparece en Pedidos de gestión → anularlo.
