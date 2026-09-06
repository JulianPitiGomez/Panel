# Dominio propio de la tienda (F6)

La tienda de cada comercio se publica en **su** dominio (`tienda.sucomercio.com.ar`
o el dominio raíz). La URL interna `/web/{slug}` sigue funcionando para pruebas y
soporte. No hay subdominio nuestro por comercio.

## Cómo funciona

1. En **Catálogo → Mi Tienda → Dominio propio** el comercio carga su dominio.
2. Nosotros le decimos qué registro DNS crear:
   - subdominio (`tienda.sucomercio.com.ar`): **CNAME** → `TIENDA_DOMINIO_CNAME`
   - dominio raíz (`sucomercio.com.ar`, no admite CNAME): **A** → `TIENDA_DOMINIO_IPS`
3. Apreta **Verificar**. `TiendaDominioService::verificar()` consulta el DNS y, si
   apunta a nosotros, marca el dominio como verificado. Quien controla la zona DNS
   controla el dominio: no hace falta otro token de propiedad.
4. Desde ahí `TiendaDominioMiddleware` (global, antes del enrutado) reconoce el host,
   reescribe el pedido a `/web/{slug}/...` y hace que todas las URLs generadas salgan
   limpias con el dominio del cliente. El sitemap, el canonical y los mails usan ese
   dominio.

Todo se guarda en el JSON de `config_clientes_web` (`tienda_dominio`,
`tienda_dominio_verificado`, ...). Sin tablas nuevas.

## Variables de entorno

```
TIENDA_DOMINIO_CNAME=tiendas.bcnsoft.com.ar      # host al que apuntan los CNAME
TIENDA_DOMINIO_IPS=190.0.0.10                    # IP(s) del servidor web, para registros A (coma)
TIENDA_DOMINIO_HOSTS_PROPIOS=panel.bcnsoft.com.ar # otros hosts nuestros que NO son tiendas (coma)
```

`APP_URL` también cuenta como host propio. Si `APP_URL` empieza con `https`, las
URLs de las tiendas salen con `https`.

## Certificado automático (Let's Encrypt)

Recomendado: **Caddy** delante de PHP-FPM, con *on-demand TLS*. Caddy pide el
certificado la primera vez que alguien entra a un dominio, lo renueva solo y antes de
emitirlo nos pregunta si el dominio está permitido:

```
{
    on_demand_tls {
        ask http://127.0.0.1/tienda/dominio-permitido
    }
}

https:// {
    tls {
        on_demand
    }
    root * /var/www/panel/public
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
}
```

`GET /tienda/dominio-permitido?domain=tienda.x.com.ar` responde `200` solo si el
dominio está verificado y la tienda habilitada; si no, `404`, y Caddy no emite nada
(así nadie nos hace pedir certificados para dominios ajenos).

Alternativa con nginx + certbot: un cron que corra
`php artisan tienda:dominios --verificados` (lista los dominios) y para cada uno
`certbot --nginx -d dominio`; más manual, funciona igual.

## Detrás de un proxy

Si Caddy o nginx terminan el TLS, Laravel tiene que confiar en `X-Forwarded-Proto`
para que `request()->getScheme()` sea `https`. Con `APP_URL=https://...` alcanza para
las URLs generadas; para cookies `Secure` configurar `trustProxies` en
`bootstrap/app.php`.

## Qué NO cubre

- Redirección de `www.` a sin `www.` (o al revés): cada variante es un dominio; cargar la que se usa.
- Mail desde el dominio del cliente: los avisos salen desde nuestra cuenta de correo, con el nombre de la tienda.
