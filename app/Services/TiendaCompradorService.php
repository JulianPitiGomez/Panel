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

    /** Busca un comprador por mail (o null). */
    public function porEmail(string $email): ?object
    {
        return $this->tabla('tienda_compradores')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first();
    }

    /**
     * Valida mail + contrasena. Devuelve el comprador si puede entrar, null si no.
     * Un comprador pendiente o rechazado no entra aunque la contrasena sea correcta.
     */
    public function autenticar(string $email, string $password): ?object
    {
        $comprador = $this->porEmail($email);

        if (!$comprador || !Hash::check($password, $comprador->password_hash)) {
            return null;
        }

        if ($comprador->estado !== self::ESTADO_APROBADO) {
            return null;
        }

        $comprador->ultimo_ingreso = now()->toDateTimeString();
        $this->tabla('tienda_compradores')
            ->where('id', $comprador->id)
            ->update(['ultimo_ingreso' => $comprador->ultimo_ingreso]);

        return $comprador;
    }

    private function passwordAleatoria(): string
    {
        // 8 caracteres legibles, sin 0/O ni 1/l, para dictarla por telefono si hace falta
        return substr(str_shuffle('23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ'), 0, 8);
    }
}
