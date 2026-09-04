<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;
use App\Services\TiendaService;
use App\Services\TiendaCompradorService;

class TiendaController extends Controller
{
    public function index($tienda)
    {
        // El middleware ya configuró sesión y DB
        // Solo decidir si mostrar login o redirigir al catálogo

        if (TiendaService::esPrivada(session('tienda_modo')) && !session('tienda_comprador_id')) {
            return view('tienda.login', [
                'tienda' => $tienda,
                'nombreTienda' => session('tienda_nombre'),
            ]);
        }

        return redirect()->route('tienda.catalogo', $tienda);
    }

    public function login(Request $request, $tienda)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // El middleware ya configuró la DB. El ingreso es contra tienda_compradores
        // (contraseña hasheada); el viejo login por DNI de clientes quedó apagado.
        $compradores = new TiendaCompradorService(session('tienda_prefix'));
        $login = $compradores->autenticar($request->email, $request->password);

        switch ($login['resultado']) {
            case TiendaCompradorService::LOGIN_OK:
                $compradores->iniciarSesion($login['comprador']);
                return redirect()->route('tienda.catalogo', $tienda);

            case TiendaCompradorService::LOGIN_PENDIENTE:
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Tu cuenta todavía está pendiente de aprobación por el comercio.']);

            case TiendaCompradorService::LOGIN_RECHAZADO:
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Tu cuenta no está habilitada. Consultá con el comercio.']);

            default:
                return back()->withInput($request->only('email'))
                    ->withErrors(['password' => 'Email o contraseña incorrectos.']);
        }
    }

    public function logout($tienda)
    {
        session()->forget(array_merge(TiendaCompradorService::clavesSesion(), ['tienda_carrito']));

        return redirect()->route('tienda.index', $tienda);
    }
}
