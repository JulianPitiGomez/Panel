<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Compradores de la tienda web (tabla {prefijo}tienda_compradores).
 *
 * Un comprador es quien entra a la tienda con mail y contrasena. Puede estar
 * enlazado a un cliente de gestion (codcli) o no. Aca vive todo lo que toca esa
 * tabla: alta masiva desde clientes, alta individual, login.
 */
class TiendaCompradorService
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';

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
     * Rutina de habilitacion: crea un comprador por cada cliente de gestion que
     * tenga mail valido, ya aprobado y enlazado por codcli.
     *
     * Reglas:
     *  - Un comprador por mail (comparado en minusculas). Si varios clientes
     *    comparten el mail, gana el CODIGO mas bajo y el resto se informa.
     *  - Los que ya existen en tienda_compradores se dejan como estan.
     *  - $passwordInicial: contrasena que reciben todos (uso en pruebas). Si es
     *    null se genera una aleatoria por comprador y se devuelve en el detalle,
     *    para que despues se le avise al cliente por el canal que corresponda.
     *
     * Devuelve un resumen con contadores y el detalle de cada caso.
     */
    public function importarDesdeClientes(?string $passwordInicial = null, bool $dryRun = false): array
    {
        $clientes = $this->tabla('clientes')
            ->select('CODIGO', 'NOMBRE', 'MAIL', 'DNI', 'TELEFONO')
            ->whereRaw('TRIM(MAIL) <> ""')
            ->orderBy('CODIGO')
            ->get();

        $existentes = $this->tabla('tienda_compradores')
            ->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [mb_strtolower($email) => $id])
            ->all();

        $resumen = [
            'creados' => 0,
            'ya_existian' => 0,
            'mail_invalido' => 0,
            'mail_repetido' => 0,
            'detalle' => [],
        ];

        $vistos = [];

        foreach ($clientes as $c) {
            $email = mb_strtolower(trim($c->MAIL));
            $nombre = trim($c->NOMBRE) ?: "Cliente {$c->CODIGO}";

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $resumen['mail_invalido']++;
                $resumen['detalle'][] = ['codcli' => $c->CODIGO, 'nombre' => $nombre, 'email' => $c->MAIL, 'resultado' => 'mail_invalido'];
                continue;
            }

            if (isset($existentes[$email])) {
                $resumen['ya_existian']++;
                $resumen['detalle'][] = ['codcli' => $c->CODIGO, 'nombre' => $nombre, 'email' => $email, 'resultado' => 'ya_existia'];
                continue;
            }

            if (isset($vistos[$email])) {
                $resumen['mail_repetido']++;
                $resumen['detalle'][] = ['codcli' => $c->CODIGO, 'nombre' => $nombre, 'email' => $email, 'resultado' => 'mail_repetido', 'gano_codcli' => $vistos[$email]];
                continue;
            }

            $vistos[$email] = $c->CODIGO;
            $password = $passwordInicial ?? $this->passwordAleatoria();

            if (!$dryRun) {
                $this->tabla('tienda_compradores')->insert([
                    'email' => $email,
                    'password_hash' => Hash::make($password),
                    'email_validado' => 1,      // es un cliente conocido del comercio
                    'nombre' => mb_substr($nombre, 0, 100),
                    'telefono' => trim((string) $c->TELEFONO) ?: null,
                    'documento' => ((int) $c->DNI) > 0 ? (string) (int) $c->DNI : null,
                    'estado' => self::ESTADO_APROBADO,
                    'codcli' => $c->CODIGO,
                    'creado' => now(),
                ]);
            }

            $resumen['creados']++;
            $resumen['detalle'][] = [
                'codcli' => $c->CODIGO,
                'nombre' => $nombre,
                'email' => $email,
                'resultado' => $dryRun ? 'se_crearia' : 'creado',
                'password' => $passwordInicial ? null : $password,
            ];
        }

        return $resumen;
    }

    // ------------------------------------------- sincronizacion con gestion

    /** Horas que dura el enlace de la invitacion (el de recuperar clave dura 48). */
    public const HORAS_INVITACION = 168;

    /**
     * Gestion manda: deja tienda_compradores al dia con el mail de cada cliente de gestion.
     *
     *  - Cliente con mail valido que todavia no es comprador: se crea INVITADO (sin
     *    contrasena: password_hash vacio, nadie puede entrar hasta que elija una con
     *    el enlace de la invitacion).
     *  - Comprador enlazado (codcli) al que le cambiaron el mail en gestion: se le
     *    cambia el mail. Si todavia no habia elegido contrasena, se lo vuelve a invitar
     *    al mail nuevo. Si el mail nuevo ya es de otro comprador, no se toca (conflicto).
     *  - Comprador sin codcli (registro web) cuyo mail aparece en gestion: se enlaza.
     *  - Mail vacio o invalido en gestion: no se hace nada (no se borra ningun comprador).
     *  - Varios clientes con el mismo mail: gana el CODIGO mas bajo.
     *
     * No manda mails: deja marcados los que hay que invitar (ver invitacionesPorEnviar()).
     * Devuelve contadores y el detalle de lo que hizo (o haria, con $dryRun).
     */
    public function sincronizarDesdeGestion(bool $dryRun = false, bool $crearInvitados = true): array
    {
        $resumen = ['creados' => 0, 'mail_cambiado' => 0, 'enlazados' => 0, 'conflictos' => 0, 'detalle' => []];

        $compradores = $this->tabla('tienda_compradores')->get(['id', 'email', 'password_hash', 'codcli']);
        $porEmail = [];
        $porCodcli = [];
        foreach ($compradores as $c) {
            $porEmail[mb_strtolower(trim($c->email))] = $c;
            if ($c->codcli) {
                $porCodcli[(int) $c->codcli] = $c;
            }
        }

        $clientes = $this->tabla('clientes')
            ->select('CODIGO', 'NOMBRE', 'MAIL', 'DNI', 'TELEFONO')
            ->whereRaw('TRIM(MAIL) <> ""')
            ->orderBy('CODIGO')
            ->get();

        $vistos = [];
        foreach ($clientes as $cli) {
            $email = mb_strtolower(trim((string) $cli->MAIL));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($vistos[$email])) {
                continue;
            }
            $vistos[$email] = true;
            $codcli = (int) $cli->CODIGO;
            $nombre = trim((string) $cli->NOMBRE) ?: "Cliente {$codcli}";
            $actual = $porCodcli[$codcli] ?? null;

            // 1) Ya es comprador enlazado a este cliente
            if ($actual) {
                if (mb_strtolower(trim($actual->email)) === $email) {
                    continue;
                }
                $otro = $porEmail[$email] ?? null;
                if ($otro && $otro->id !== $actual->id) {
                    $resumen['conflictos']++;
                    $resumen['detalle'][] = ['codcli' => $codcli, 'nombre' => $nombre, 'email' => $email, 'resultado' => 'conflicto (el mail ya es del comprador #' . $otro->id . ')'];
                    continue;
                }
                if (!$dryRun) {
                    $cambios = ['email' => $email];
                    if ($actual->password_hash === '') {
                        // Invitado que nunca entro: se lo invita de nuevo al mail nuevo
                        $cambios += ['token_validacion' => null, 'token_vence' => null];
                    }
                    $this->tabla('tienda_compradores')->where('id', $actual->id)->update($cambios);
                }
                unset($porEmail[mb_strtolower(trim($actual->email))]);
                $actual->email = $email;
                $porEmail[$email] = $actual;
                $resumen['mail_cambiado']++;
                $resumen['detalle'][] = ['codcli' => $codcli, 'nombre' => $nombre, 'email' => $email, 'resultado' => 'mail cambiado'];
                continue;
            }

            // 2) El mail ya es de un comprador (registro web o enlazado a otro cliente)
            if (isset($porEmail[$email])) {
                $otro = $porEmail[$email];
                if (!$otro->codcli) {
                    if (!$dryRun) {
                        $this->tabla('tienda_compradores')->where('id', $otro->id)->update(['codcli' => $codcli]);
                    }
                    $otro->codcli = $codcli;
                    $porCodcli[$codcli] = $otro;
                    $resumen['enlazados']++;
                    $resumen['detalle'][] = ['codcli' => $codcli, 'nombre' => $nombre, 'email' => $email, 'resultado' => 'enlazado al comprador #' . $otro->id];
                }
                continue;
            }

            // 3) Cliente nuevo para la tienda: se crea invitado (si el comercio no lo apago)
            if (!$crearInvitados) {
                continue;
            }
            if (!$dryRun) {
                $id = $this->tabla('tienda_compradores')->insertGetId([
                    'email' => $email,
                    'password_hash' => '',          // sin contrasena hasta que acepte la invitacion
                    'email_validado' => 0,
                    'nombre' => mb_substr($nombre, 0, 100),
                    'telefono' => mb_substr(trim((string) $cli->TELEFONO), 0, 40) ?: null,
                    'documento' => ((int) $cli->DNI) > 0 ? (string) (int) $cli->DNI : null,
                    'estado' => self::ESTADO_APROBADO,
                    'codcli' => $codcli,
                    'creado' => now(),
                ]);
                $nuevo = (object) ['id' => $id, 'email' => $email, 'password_hash' => '', 'codcli' => $codcli];
                $porEmail[$email] = $nuevo;
                $porCodcli[$codcli] = $nuevo;
            }
            $resumen['creados']++;
            $resumen['detalle'][] = ['codcli' => $codcli, 'nombre' => $nombre, 'email' => $email, 'resultado' => $dryRun ? 'se invitaria' : 'invitado'];
        }

        return $resumen;
    }

    /**
     * Invitados a los que todavia no se les mando el enlace (recien creados o con el
     * mail cambiado). Los que ya tienen un enlace, vigente o vencido, no entran: el
     * reenvio de los vencidos es a pedido del comercio (reenviarInvitacionesVencidas).
     */
    public function invitacionesPorEnviar(int $limite): array
    {
        return $this->tabla('tienda_compradores')
            ->where('password_hash', '')
            ->whereNull('token_vence')
            ->where('estado', self::ESTADO_APROBADO)
            ->orderBy('id')
            ->limit($limite)
            ->get()
            ->all();
    }

    /** Vuelve a poner en cola a los invitados cuyo enlace vencio sin usar. Devuelve cuantos. */
    public function reenviarInvitacionesVencidas(): int
    {
        return $this->tabla('tienda_compradores')
            ->where('password_hash', '')
            ->whereNotNull('token_vence')
            ->where('token_vence', '<', now())
            ->update(['token_validacion' => null, 'token_vence' => null]);
    }

    /** Cuantos compradores hay activos, invitados esperando y con la invitacion vencida. */
    public function resumenInvitaciones(): array
    {
        $t = fn () => $this->tabla('tienda_compradores');

        return [
            'activos' => $t()->where('password_hash', '<>', '')->where('email_validado', 1)->count(),
            'invitados' => $t()->where('password_hash', '')->where(fn ($q) => $q->whereNull('token_vence')->orWhere('token_vence', '>=', now()))->count(),
            'vencidos' => $t()->where('password_hash', '')->whereNotNull('token_vence')->where('token_vence', '<', now())->count(),
        ];
    }

    /** Busca un comprador por mail (o null). */
    public function porEmail(string $email): ?object
    {
        return $this->tabla('tienda_compradores')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first();
    }

    public const LOGIN_OK = 'ok';
    public const LOGIN_CREDENCIALES = 'credenciales';   // mail o contrasena incorrectos (no se dice cual)
    public const LOGIN_PENDIENTE = 'pendiente';          // cuenta esperando aprobacion del comercio
    public const LOGIN_RECHAZADO = 'rechazado';
    public const LOGIN_SIN_VALIDAR = 'sin_validar';      // registro web sin confirmar el mail
    public const LOGIN_SIN_CLAVE = 'sin_clave';          // invitado desde gestion que todavia no eligio contrasena

    // ----------------------------------------------------------- registro (F5)

    /**
     * Registro abierto (tienda publica). El comprador queda sin validar hasta que
     * confirma el mail; si el comercio pide aprobacion, ademas queda pendiente.
     * Devuelve ['ok' => bool, 'error' => ?string, 'comprador' => ?object, 'token' => ?string].
     */
    public function registrar(string $email, string $password, string $nombre, ?string $telefono, bool $requiereAprobacion): array
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Ingresá un mail válido.'];
        }
        if (mb_strlen($password) < 6) {
            return ['ok' => false, 'error' => 'La contraseña tiene que tener al menos 6 caracteres.'];
        }
        if (trim($nombre) === '') {
            return ['ok' => false, 'error' => 'Ingresá tu nombre.'];
        }

        $existente = $this->porEmail($email);
        if ($existente) {
            if (!$existente->email_validado) {
                // Se registro y nunca valido: se le manda el mail de nuevo con contrasena nueva
                $token = $this->nuevoToken((int) $existente->id);
                $this->tabla('tienda_compradores')->where('id', $existente->id)->update([
                    'password_hash' => Hash::make($password), 'nombre' => mb_substr(trim($nombre), 0, 100), 'telefono' => trim((string) $telefono) ?: null,
                ]);
                return ['ok' => true, 'error' => null, 'comprador' => $this->porEmail($email), 'token' => $token];
            }
            return ['ok' => false, 'error' => 'Ese mail ya tiene una cuenta. Si no recordás la contraseña, recuperala.'];
        }

        // Si el mail coincide con un cliente de gestion, se enlaza de entrada (con su lista de precios)
        $codcli = $this->tabla('clientes')->whereRaw('LOWER(TRIM(MAIL)) = ?', [$email])->orderBy('CODIGO')->value('CODIGO');

        $id = $this->tabla('tienda_compradores')->insertGetId([
            'email' => $email,
            'password_hash' => Hash::make($password),
            'email_validado' => 0,
            'nombre' => mb_substr(trim($nombre), 0, 100),
            'telefono' => mb_substr(trim((string) $telefono), 0, 40) ?: null,
            'estado' => $requiereAprobacion ? self::ESTADO_PENDIENTE : self::ESTADO_APROBADO,
            'codcli' => $codcli ?: null,
            'creado' => now(),
        ]);
        $token = $this->nuevoToken($id);

        return ['ok' => true, 'error' => null, 'comprador' => $this->tabla('tienda_compradores')->where('id', $id)->first(), 'token' => $token];
    }

    /** Genera y guarda un token (validacion de mail, cambio de clave o invitacion), por defecto valido 48 hs. */
    public function nuevoToken(int $id, int $horas = 48): string
    {
        $token = bin2hex(random_bytes(24));
        $this->tabla('tienda_compradores')->where('id', $id)->update([
            'token_validacion' => hash('sha256', $token),
            'token_vence' => now()->addHours($horas),
        ]);
        return $token;
    }

    /** Comprador con ese token vigente, o null. */
    public function porToken(string $token): ?object
    {
        if ($token === '') {
            return null;
        }
        return $this->tabla('tienda_compradores')
            ->where('token_validacion', hash('sha256', $token))
            ->where('token_vence', '>=', now())
            ->first();
    }

    /** Confirma el mail con el token del enlace. Devuelve el comprador o null si el enlace no sirve. */
    public function validarEmail(string $token): ?object
    {
        $c = $this->porToken($token);
        if (!$c) {
            return null;
        }
        $this->tabla('tienda_compradores')->where('id', $c->id)->update([
            'email_validado' => 1, 'token_validacion' => null, 'token_vence' => null,
        ]);
        return $this->tabla('tienda_compradores')->where('id', $c->id)->first();
    }

    /** Cambia la contrasena con el token del enlace de recuperacion. */
    public function cambiarClave(string $token, string $password): ?object
    {
        if (mb_strlen($password) < 6) {
            return null;
        }
        $c = $this->porToken($token);
        if (!$c) {
            return null;
        }
        $this->tabla('tienda_compradores')->where('id', $c->id)->update([
            'password_hash' => Hash::make($password), 'email_validado' => 1, 'token_validacion' => null, 'token_vence' => null,
        ]);
        return $this->tabla('tienda_compradores')->where('id', $c->id)->first();
    }

    /** Compradores que esperan aprobacion del comercio. */
    public function pendientes(): array
    {
        return $this->tabla('tienda_compradores')->where('estado', self::ESTADO_PENDIENTE)->orderBy('creado')->get()->all();
    }

    public function cambiarEstado(int $id, string $estado, ?string $motivo = null): void
    {
        $this->tabla('tienda_compradores')->where('id', $id)->update([
            'estado' => $estado,
            'motivo_rechazo' => $estado === self::ESTADO_RECHAZADO ? mb_substr((string) $motivo, 0, 200) : null,
        ]);
    }

    /**
     * Primera compra de un comprador web sin cliente de gestion: se lo crea en clientes
     * (consumidor final, lista 1) y se lo enlaza. Devuelve el CODIGO nuevo.
     */
    public function crearClienteGestion(object $comprador, array $direccion = []): int
    {
        $existente = $this->tabla('clientes')->whereRaw('LOWER(TRIM(MAIL)) = ?', [mb_strtolower($comprador->email)])->orderBy('CODIGO')->value('CODIGO');
        if ($existente) {
            $this->tabla('tienda_compradores')->where('id', $comprador->id)->update(['codcli' => $existente]);
            return (int) $existente;
        }

        $codigo = (int) $this->tabla('clientes')->insertGetId([
            'NOMBRE' => mb_substr(mb_strtoupper(trim($comprador->nombre)), 0, 50),
            'DIRECCION' => mb_substr(trim(($direccion['calle'] ?? '') . ' ' . ($direccion['numero'] ?? '')), 0, 50),
            'LOCALIDAD' => mb_substr(trim($direccion['localidad'] ?? ''), 0, 30),
            'TELEFONO' => mb_substr(trim((string) $comprador->telefono), 0, 30),
            'CUIT' => '',
            'CONIVA' => 5,          // consumidor final
            'VENDEDOR' => 0,
            'DNI' => (int) preg_replace('/\D/', '', (string) ($comprador->documento ?? '')) ?: 0,
            'SALDO' => 0,
            'MAIL' => mb_substr($comprador->email, 0, 50),
            'CODPOS' => mb_substr(trim($direccion['cp'] ?? ''), 0, 8) ?: null,
            'FECING' => today()->toDateString(),
            'LISPRE' => 1,
            'LIMITE' => 0,
            'USUARIO' => 'TIENDA',
        ], 'CODIGO');

        $this->tabla('tienda_compradores')->where('id', $comprador->id)->update(['codcli' => $codigo]);

        return $codigo;
    }

    /**
     * Valida mail + contrasena. Devuelve ['resultado' => LOGIN_*, 'comprador' => object|null].
     * Un comprador pendiente o rechazado no entra aunque la contrasena sea correcta,
     * pero se le explica por que.
     */
    public function autenticar(string $email, string $password): array
    {
        $comprador = $this->porEmail($email);

        if ($comprador && $comprador->password_hash === '' && $comprador->estado === self::ESTADO_APROBADO) {
            return ['resultado' => self::LOGIN_SIN_CLAVE, 'comprador' => $comprador];
        }

        if (!$comprador || !Hash::check($password, $comprador->password_hash)) {
            return ['resultado' => self::LOGIN_CREDENCIALES, 'comprador' => null];
        }

        if (!$comprador->email_validado) {
            return ['resultado' => self::LOGIN_SIN_VALIDAR, 'comprador' => $comprador];
        }

        if ($comprador->estado === self::ESTADO_PENDIENTE) {
            return ['resultado' => self::LOGIN_PENDIENTE, 'comprador' => $comprador];
        }

        if ($comprador->estado !== self::ESTADO_APROBADO) {
            return ['resultado' => self::LOGIN_RECHAZADO, 'comprador' => $comprador];
        }

        $comprador->ultimo_ingreso = now()->toDateTimeString();
        $this->tabla('tienda_compradores')
            ->where('id', $comprador->id)
            ->update(['ultimo_ingreso' => $comprador->ultimo_ingreso]);

        return ['resultado' => self::LOGIN_OK, 'comprador' => $comprador];
    }

    /**
     * Arma la sesion del comprador logueado.
     *
     * 'tienda_comprador_id' sigue siendo el CODIGO de clientes de gestion (codcli),
     * porque asi lo usan el alta del pedido (pedidos_encab.codcli) y Mis Pedidos.
     * El id propio de la tienda va en 'tienda_comprador_web_id' para el satelite.
     * Si el comprador no esta enlazado a un cliente de gestion (registro web, F5),
     * lista de precios, descuento y vendedor quedan vacios y aplica el precio publico.
     */
    public function iniciarSesion(object $comprador): void
    {
        $cliente = $comprador->codcli
            ? $this->tabla('clientes')->where('CODIGO', $comprador->codcli)->first()
            : null;

        session([
            'tienda_comprador_web_id' => $comprador->id,
            'tienda_comprador_id' => $comprador->codcli ?: null,
            'tienda_comprador_nombre' => $cliente->NOMBRE ?? $comprador->nombre,
            'tienda_comprador_email' => $comprador->email,
            'tienda_comprador_lispre' => $cliente->LISPRE ?? null,
            'tienda_comprador_lispreesp' => $cliente->lispreesp ?? $cliente->LISPREESP ?? null,
            'tienda_comprador_descuento' => $cliente->DESCUENTO ?? 0,
            'tienda_comprador_vendedor' => $cliente->VENDEDOR ?? null,
        ]);
    }

    /** Claves de sesion que deja iniciarSesion(), para cerrarla completa. */
    public static function clavesSesion(): array
    {
        return [
            'tienda_comprador_web_id',
            'tienda_comprador_id',
            'tienda_comprador_nombre',
            'tienda_comprador_email',
            'tienda_comprador_lispre',
            'tienda_comprador_lispreesp',
            'tienda_comprador_descuento',
            'tienda_comprador_vendedor',
        ];
    }

    private function passwordAleatoria(): string
    {
        // 8 caracteres legibles, sin 0/O ni 1/l, para dictarla por telefono si hace falta
        return substr(str_shuffle('23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ'), 0, 8);
    }
}
