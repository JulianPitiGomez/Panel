<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Cliente;

class TiendaController extends Controller
{
    public function index($tienda)
    {
        // El middleware ya configuró sesión y DB
        // Solo decidir si mostrar login o redirigir al catálogo

        if (session('tienda_modo') == 1 && !session('tienda_comprador_id')) {
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

        // El middleware ya configuró la DB
        $tablePrefix = session('tienda_prefix');

        $comprador = DB::connection('client_db')
            ->table("{$tablePrefix}clientes")
            ->where('MAIL', $request->email)
            ->first();

        if (!$comprador) {
            return back()->withErrors(['email' => 'Email no registrado']);
        }

        if ($request->password != $comprador->DNI) {
            return back()->withErrors(['password' => 'Contraseña incorrecta']);
        }

        session([
            'tienda_comprador_id' => $comprador->CODIGO,
            'tienda_comprador_nombre' => $comprador->NOMBRE,
            'tienda_comprador_email' => $request->email,
            'tienda_comprador_lispre' => $comprador->LISPRE,
            'tienda_comprador_lispreesp' => $comprador->lispreesp ?? $comprador->LISPREESP ?? null,
            'tienda_comprador_descuento' => $comprador->DESCUENTO ?? 0,
            'tienda_comprador_vendedor' => $comprador->VENDEDOR ?? null,
        ]);

        return redirect()->route('tienda.catalogo', $tienda);
    }

    public function logout($tienda)
    {
        session()->forget([
            'tienda_comprador_id',
            'tienda_comprador_nombre',
            'tienda_comprador_email',
            'tienda_comprador_lispre',
            'tienda_comprador_lispreesp',
            'tienda_comprador_descuento',
            'tienda_comprador_vendedor',
            'tienda_carrito',
        ]);

        return redirect()->route('tienda.index', $tienda);
    }
}
