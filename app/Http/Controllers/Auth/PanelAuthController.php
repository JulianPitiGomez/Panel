<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Cliente;

class PanelAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.panel-login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'mail' => 'required|email',
            'password' => 'required',
        ]);

        // Buscar por la combinación de email + code para permitir múltiples sistemas con el mismo email
        $cliente = Cliente::where('mail', $request->mail)
                          ->where('code', $request->password)
                          ->first();

        if ($cliente) {
            session([
                'panel_user_id' => $cliente->id,
                'client_id' => $cliente->id,
                'active_module' => 'panel',
                'cliente_nombre' => $cliente->nombre
            ]);

            // Redirigir según el pack del cliente
            if ($cliente->pack == 'GE3') {
                return redirect()->route('panel.dashboard');
            } else {
                return redirect()->route('panelresto.dashboard');
            }
        }

        return back()->withErrors(['mail' => 'Credenciales incorrectas']);
    }

    public function logout()
    {
        session()->forget(['panel_user_id', 'client_id', 'active_module']);
        session()->regenerate();
        return redirect()->route('home');
    }
}