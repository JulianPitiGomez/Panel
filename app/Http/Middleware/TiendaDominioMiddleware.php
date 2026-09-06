<?php

namespace App\Http\Middleware;

use App\Services\TiendaDominioService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Dominio propio (F6). Corre antes del enrutado, para todos los pedidos:
 *  - Si el host NO es nuestro y es el dominio verificado de una tienda, el pedido
 *    se reescribe a /web/{slug}{ruta} y sigue por las rutas de siempre. Las URLs
 *    que genera la aplicacion salen sin el /web/{slug} y con el dominio del cliente.
 *  - Si el host es nuestro (panel, /web/{slug}) o desconocido, no toca nada.
 *
 * Lo que no se reescribe: Livewire, assets, imagenes, webhooks de MP y /up, que
 * son los mismos en cualquier host.
 */
class TiendaDominioMiddleware
{
    private const NO_REESCRIBIR = '#^/(livewire|build|img|images|storage|vendor|mp|up|tienda/dominio-permitido)(/|$)#';

    public function handle(Request $request, Closure $next)
    {
        $host = strtolower($request->getHost());
        $cliente = ($host === '' || TiendaDominioService::esHostPropio($host)) ? null : TiendaDominioService::clientePorHost($host);
        if (!$cliente) {
            // Host nuestro o desconocido: URLs normales (y se limpia lo que haya dejado un pedido anterior
            // en el mismo proceso, por si se corre con Octane o en tests)
            URL::forceRootUrl(null);
            URL::formatPathUsing(fn ($path) => $path);

            return $next($request);
        }
        $slug = $cliente->tienda;

        // URLs limpias: raiz en el dominio del cliente y sin el /web/{slug}
        $esquema = str_starts_with((string) config('app.url'), 'https') ? 'https' : $request->getScheme();
        URL::forceRootUrl($esquema . '://' . $host);
        $patron = '#^/web/' . preg_quote($slug, '#') . '(?=/|$)#i';
        URL::formatPathUsing(function ($path) use ($patron) {
            $limpio = preg_replace($patron, '', $path);

            return $limpio === '' ? '/' : $limpio;
        });

        $path = '/' . ltrim($request->getPathInfo(), '/');
        if (preg_match(self::NO_REESCRIBIR, $path) || preg_match('#^/web(/|$)#i', $path)) {
            return $next($request);
        }

        $uri = '/web/' . $slug . ($path === '/' ? '' : $path);
        $qs = $request->getQueryString();
        $server = $request->server->all();
        $server['REQUEST_URI'] = $uri . ($qs !== null && $qs !== '' ? '?' . $qs : '');
        unset($server['PATH_INFO'], $server['ORIG_PATH_INFO']);

        $reescrito = $request->duplicate(null, null, null, null, null, $server);
        $reescrito->attributes->set('tienda_dominio', $host);

        return $next($reescrito);
    }
}
