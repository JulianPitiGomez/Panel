# Tienda web PyMEs: pasos para salir a producción

Lista de lo que hay que hacer al habilitar la tienda en producción. Se va
completando a medida que aparece algo nuevo en las pruebas locales.

Para la primera subida (clientes 1 y 2409), con la lista de archivos uno por uno, ver
[subida-produccion.md](subida-produccion.md).

## Una sola vez (servidor)

- [ ] `.env` de producción:
  - `TIENDA_CLIENTES_HABILITADOS` con los clientes que arrancan (vacío = todos).
  - `TIENDA_OFRECER_SERVICIO=false` hasta que el producto esté listo para ofrecerlo.
  - `TIENDA_DOMINIO_CNAME` y `TIENDA_DOMINIO_IPS` con el destino / IP **reales** del servidor.
    Nunca `127.0.0.1`: eso es solo para las pruebas locales con el archivo `hosts`.
  - R2 (`R2_*`, `IMAGENES_PRODUCTOS_DISK=r2`), Google Maps (`GOOGLE_MAPS_API_KEY` con el dominio
    del panel autorizado, `GOOGLE_MAPS_SERVER_KEY`), Mercado Pago, `ANTHROPIC_API_KEY`.
- [ ] `npm run build` (el CSS de la tienda se compila; sin esto el diseño se ve roto).
- [ ] Cron de Laravel (`schedule:run` cada minuto). Lo programado en `routes/console.php`:
  `tienda:avisos-pendientes` (cada 5 min) y `tienda:carritos-abandonados` (diario).
  Además programar `tienda:conciliar-comisiones`.
- [ ] Dominios propios: Caddy con TLS on-demand, ver [dominio-propio.md](dominio-propio.md).

## Por cada cliente que se habilita

1. **Tablas**: correr `esquema-tienda-v1.sql` en la base del cliente, reemplazando `{p}` por su
   prefijo (`ge_` + id con 6 dígitos). El mismo archivo crea las tablas que no existen
   (incluida `articu_fotos`) y agrega las columnas que falten en las que ya estaban; se puede
   correr de nuevo sin romper nada. Por ejemplo, para el cliente 2409:

   ```
   sed "s/{p}/ge_002409/g" docs/tienda/esquema-tienda-v1.sql | mysql -u... bcn_base81
   ```

   Después comparar las columnas contra una base que funcione (`information_schema.columns`):
   el archivo ya se desfasó una vez (le faltaban 6 columnas de `tienda_pedidos`, corregido el
   2026-10-09). Anotar `tienda_esquema_version` en `config_clientes_web`: sin eso el botón
   "Link para el cliente" del preventista no aparece.
2. **Nombre de la tienda**: `config_clientes.tienda` (el slug de `/web/{slug}`) no puede estar
   vacío; sin eso la tienda responde "no disponible".
3. **Compradores**: los clientes de gestión con mail pasan a ser compradores de la tienda
   (ingresan con mail y contraseña; el ingreso por DNI está apagado).

   ```
   php artisan tienda:importar-compradores {cliente_id} --dry-run
   php artisan tienda:importar-compradores {cliente_id}
   ```

   Sin `--password` genera una contraseña al azar por comprador y la lista: guardar esa salida
   para entregársela a cada uno. `--password=` (una para todos) es **solo para pruebas**.
   Los clientes con mail vacío o inválido (muchos tienen `0`) se saltean: el comercio tiene que
   cargarles el mail en gestión y volver a correr el comando (los ya creados no se duplican).
   Pendiente decidir: cómo se entrega la contraseña inicial (¿mail automático con link para
   crearla?) y si esto pasa a ser un botón del panel o de gestión.
4. **Fotos** (si el cliente las manda en un Excel con código y link):

   ```
   php artisan tienda:importar-fotos {cliente_id} "archivo.xlsx" --dry-run --verificar-links
   php artisan tienda:importar-fotos {cliente_id} "archivo.xlsx"
   ```

   Columnas por defecto: código en A, link en J (`--col-codigo`, `--col-foto`). Saltea los
   artículos que ya tienen foto (salvo `--pisar`).

## Clientes ya preparados en local

- **2409 DAME FUEGO** (`bcn_base81`): las 1221 fotos ya están en R2. En producción **no hace
  falta volver a bajarlas**: alcanza con copiar las filas de `ge_002409tienda_fotos` y
  `ge_002409articu_fotos` de la base local. Su slug local es `dame-fuego` y su dominio de
  prueba `damefuego.com.ar` (verificado a mano en local; en producción se verifica de verdad).
