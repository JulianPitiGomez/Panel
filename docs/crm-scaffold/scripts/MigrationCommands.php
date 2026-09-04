<?php
// ================================================================
// SCRIPTS DE MIGRACIÓN DE DATOS
// Ejecutar con: php artisan migrate:data
// O individualmente: php artisan tinker < script.php
// ================================================================

// ================================================================
// 1) MIGRAR CONTACTOS (bcn_config.contactos → crm_app.contacts)
// ================================================================
// app/Console/Commands/MigrateContacts.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateContacts extends Command
{
    protected $signature = 'migrate:contacts {--dry-run : Solo muestra cuántos registros se migrarían}';
    protected $description = 'Migra contactos desde bcn_config.contactos a crm_app.contacts';

    public function handle(): int
    {
        $this->info('Migrando contactos desde bcn_config...');

        $contactos = DB::connection('bcnsoft')
            ->table('contactos')
            ->select('*')
            ->orderBy('id')
            ->get();

        $this->info("Encontrados: {$contactos->count()} contactos");

        if ($this->option('dry-run')) {
            $this->info('DRY RUN - No se realizaron cambios');
            return 0;
        }

        $bar = $this->output->createProgressBar($contactos->count());
        $migrated = 0;
        $skipped = 0;

        foreach ($contactos as $c) {
            // Verificar si ya existe por teléfono
            $exists = DB::table('contacts')->where('phone', $c->telefono)->exists();
            if ($exists) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Determinar system_source basado en tipo
            $systemSource = match ((int) $c->tipo) {
                1 => 'bcnsoft',    // PYME
                2 => 'bcnsoft',    // RESTO
                3 => 'pedidosbcn', // PEDIDOS BCN
                default => null,
            };

            DB::table('contacts')->insert([
                'name' => $c->nombre ?? 'Sin nombre',
                'business_name' => $c->nombre_com,
                'email' => $c->email,
                'phone' => $c->telefono,
                'type' => (int) $c->tipo,
                'status' => (int) ($c->estado ?? 1),
                'pack' => $c->pack,
                'client_id' => $c->id_cliente > 0 ? $c->id_cliente : null,
                'system_source' => $systemSource,
                'assigned_to' => $c->id_usuario > 0 ? $c->id_usuario : null,
                'rubro_id' => (int) ($c->id_rubro ?? 0),
                'discard_reason_id' => $c->motivo_desc,
                'rubro_tienda' => (int) ($c->rubro_tienda ?? 0),
                'notes' => $c->observa,
                'contact_date' => $c->fechacont,
                'contact_time' => $c->horacont,
                'created_time' => $c->hora,
                'created_at' => $c->fechacrea ? $c->fechacrea . ' ' . ($c->hora ?? '00:00:00') : now(),
                'updated_at' => now(),
            ]);

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Migrar también los detalles (contactos_det → contact_notes)
        $this->info('Migrando notas de contactos...');
        $detalles = DB::connection('bcnsoft')
            ->table('contactos_det')
            ->select('*')
            ->get();

        $notesCount = 0;
        foreach ($detalles as $d) {
            // Buscar el contacto migrado por el id original
            // Necesitamos mapear el id_contacto original al nuevo
            // Para esto usamos el teléfono como clave
            $original = DB::connection('bcnsoft')
                ->table('contactos')
                ->where('id', $d->id_contacto)
                ->first();

            if (!$original || !$original->telefono) continue;

            $newContact = DB::table('contacts')
                ->where('phone', $original->telefono)
                ->first();

            if (!$newContact) continue;

            DB::table('contact_notes')->insert([
                'contact_id' => $newContact->id,
                'note_date' => $d->fecha,
                'content' => $d->observa,
                'created_at' => $d->fecha ? $d->fecha . ' 00:00:00' : now(),
                'updated_at' => now(),
            ]);
            $notesCount++;
        }

        // Migrar motivos de descarte
        $this->info('Migrando motivos de descarte...');
        $motivos = DB::connection('bcnsoft')
            ->table('contactos_motivos_desc')
            ->get();

        foreach ($motivos as $m) {
            DB::table('contact_discard_reasons')->insert([
                'id' => $m->id,
                'name' => $m->nombre,
            ]);
        }

        $this->info("Migración completada:");
        $this->info("  Contactos migrados: {$migrated}");
        $this->info("  Contactos saltados (duplicados): {$skipped}");
        $this->info("  Notas migradas: {$notesCount}");
        $this->info("  Motivos de descarte: {$motivos->count()}");

        return 0;
    }
}


// ================================================================
// 2) MIGRAR PENDIENTES DE DESARROLLO
//    (bcn_config.pendientes → crm_app.dev_tasks)
// ================================================================

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDevTasks extends Command
{
    protected $signature = 'migrate:dev-tasks {--dry-run}';
    protected $description = 'Migra pendientes de desarrollo desde bcn_config.pendientes a crm_app.dev_tasks';

    public function handle(): int
    {
        $this->info('Migrando pendientes de desarrollo...');

        $pendientes = DB::connection('bcnsoft')
            ->table('pendientes')
            ->select('*')
            ->orderBy('id')
            ->get();

        $this->info("Encontrados: {$pendientes->count()} pendientes");

        if ($this->option('dry-run')) {
            return 0;
        }

        // Mapeo de usuario legacy → user_id nuevo
        // Se necesita ajustar según los usuarios migrados
        $userMap = $this->buildUserMap();

        $bar = $this->output->createProgressBar($pendientes->count());
        $count = 0;

        foreach ($pendientes as $p) {
            $userId = $userMap[$p->usuario] ?? null;

            $taskId = DB::table('dev_tasks')->insertGetId([
                'type' => (int) ($p->tipo ?? 1),
                'status' => (int) ($p->estado ?? 1),
                'priority' => (int) ($p->prioridad ?? 1),
                'title' => $p->nombre ?? 'Sin título',
                'client_name' => $p->cliente,
                'description' => $p->descripcion,
                'notes' => $p->anotaciones,
                'client_description' => $p->descripcion_cli,
                'show_to_client' => (bool) $p->lMostrar_cli,
                'created_by' => $userId,
                'task_date' => $p->fecha,
                'uploaded_date' => $p->fecha_subido,
                'estimated_date' => $p->fecha_estimada,
                'created_at' => $p->fecha ? $p->fecha . ' 00:00:00' : now(),
                'updated_at' => now(),
            ]);

            // Migrar las marcas de lectura (lPoli, lAgus, etc.)
            // Mapeo hardcodeado basado en los nombres
            $readFields = [
                'lPoli' => 'Poli',
                'lAgus' => 'Agus',
                'lJuli' => 'Juli',
                'lPica' => 'Pica',
                'lValen' => 'Valen',
            ];

            foreach ($readFields as $field => $nombre) {
                if (isset($p->$field) && $p->$field) {
                    $uid = $userMap[$nombre] ?? null;
                    if ($uid) {
                        DB::table('dev_task_reads')->insert([
                            'dev_task_id' => $taskId,
                            'user_id' => $uid,
                            'read' => true,
                            'read_at' => now(),
                        ]);
                    }
                }
            }

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrados: {$count} pendientes de desarrollo");

        return 0;
    }

    private function buildUserMap(): array
    {
        // Mapea nombre de usuario legacy → ID en tabla users del CRM
        $users = DB::table('users')->pluck('id', 'name')->toArray();
        $legacyUsers = DB::connection('pedidosbcn')
            ->table('usuariop')
            ->pluck('id', 'nombre')
            ->toArray();

        // Intentar mapear por nombre
        $map = [];
        foreach ($legacyUsers as $nombre => $legacyId) {
            // Buscar por nombre exacto o parcial
            foreach ($users as $newName => $newId) {
                if (stripos($newName, $nombre) !== false || stripos($nombre, $newName) !== false) {
                    $map[$nombre] = $newId;
                    break;
                }
            }
        }

        return $map;
    }
}


// ================================================================
// 3) MIGRAR FACTURAS LEGACY
//    (bcn_config.config_ventas_encab → crm_app.invoices)
// ================================================================

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyInvoices extends Command
{
    protected $signature = 'migrate:invoices {--dry-run}';
    protected $description = 'Migra facturas legacy desde bcn_config.config_ventas_encab a crm_app.invoices';

    public function handle(): int
    {
        $this->info('Migrando facturas legacy...');

        $facturas = DB::connection('bcnsoft')
            ->table('config_ventas_encab')
            ->select('*')
            ->get();

        $this->info("Encontradas: {$facturas->count()} facturas");

        if ($this->option('dry-run')) {
            return 0;
        }

        // Primero, necesitamos los billing_profiles creados
        // basados en perfiles_fact
        $this->migrateProfiles();

        $profileMap = DB::table('billing_profiles')
            ->pluck('id', 'legacy_perfilfact_id')
            ->toArray();

        $bar = $this->output->createProgressBar($facturas->count());
        $count = 0;

        foreach ($facturas as $f) {
            $profileId = $profileMap[$f->id_perfil] ?? null;
            if (!$profileId) {
                $bar->advance();
                continue;
            }

            // Determinar tipo de comprobante
            $letra = $f->LETRA ?? '';
            $tipoComp = $f->TICOMP ?? 'FA';
            $voucherType = match ($tipoComp) {
                'FA' => 'F' . $letra,
                'NC' => 'NC' . $letra,
                'ND' => 'ND' . $letra,
                default => 'FC',
            };

            DB::table('invoices')->insert([
                'billing_profile_id' => $profileId,
                'client_id' => (int) ($f->CODCLI ?? 0),
                'system_source' => 'bcnsoft',
                'client_name' => $f->NOMBRE,
                'client_cuit' => $f->CUIT,
                'client_tax_condition' => $f->CONIVA,
                'client_address' => $f->DIRECCION,
                'client_locality' => $f->LOCALIDAD,
                'client_email' => $f->mail,
                'voucher_type' => $voucherType,
                'point_of_sale' => 0, // No disponible en legacy
                'voucher_number' => (int) str_replace('-', '', $f->NUMCOMP ?? '0'),
                'cae' => $f->cae,
                'cae_expiration' => $f->fecvto,
                'net_amount' => (float) ($f->NETO ?? 0),
                'iva_amount' => (float) ($f->IVA ?? 0),
                'iibb_amount' => (float) ($f->IIBB ?? 0),
                'perception_amount' => (float) ($f->PERCEP ?? 0),
                'total_amount' => (float) ($f->IMPORTE ?? 0),
                'concept' => 2, // Servicios
                'description' => $f->OBSERVA,
                'status' => $f->cae ? 'emitted' : 'draft',
                'payment_received' => true,
                'legacy_pago_id' => $f->id_pago,
                'issued_at' => $f->FECHA,
                'created_at' => $f->FECHA ? $f->FECHA . ' 00:00:00' : now(),
                'updated_at' => now(),
            ]);

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Facturas migradas: {$count}");

        return 0;
    }

    private function migrateProfiles(): void
    {
        $this->info('Migrando perfiles de facturación...');

        $perfilesFact = DB::connection('bcnsoft')
            ->table('perfiles_fact')
            ->get();

        $perfilesMP = DB::connection('bcnsoft')
            ->table('perfiles')
            ->get()
            ->keyBy('id');

        foreach ($perfilesFact as $pf) {
            // Verificar si ya existe
            if (DB::table('billing_profiles')->where('legacy_perfilfact_id', $pf->id)->exists()) {
                continue;
            }

            $mp = $perfilesMP[$pf->id] ?? null;

            DB::table('billing_profiles')->insert([
                'name' => $pf->nombre ?? 'Perfil ' . $pf->id,
                'cuit' => $pf->cuit ?? '',
                'business_name' => $pf->nombre ?? '',
                'tax_condition' => (int) ($pf->coniva ?? 6),
                'address' => $pf->direccion,
                'locality' => $pf->localidad,
                'ingresos_brutos' => $pf->ing_bruto,
                'activity_start' => $pf->ini_acti,
                'point_of_sale' => (int) ($pf->prefijo ?? 1),
                'suffix' => $pf->sufijo,
                'billing_limit' => (float) ($pf->limite ?? 0),
                'cbu' => $mp->cbu ?? null,
                'alias' => $mp->alias ?? null,
                'phone' => $mp->telefono ?? null,
                'mp_token' => $mp->mp_token ?? null,
                'collected' => (float) ($mp->cobrado ?? 0),
                'collected_iva' => (float) ($mp->cobrado_iva ?? 0),
                'collection_count' => (int) ($mp->cobros ?? 0),
                'is_active' => (bool) ($mp->activo ?? true),
                'legacy_perfil_id' => $mp->id ?? null,
                'legacy_perfilfact_id' => $pf->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count = DB::table('billing_profiles')->count();
        $this->info("Perfiles migrados: {$count}");
    }
}


// ================================================================
// 4) MIGRAR USUARIOS
//    (pedidospru.usuariop → crm_app.users)
// ================================================================

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateUsers extends Command
{
    protected $signature = 'migrate:users {--dry-run}';
    protected $description = 'Migra usuarios desde pedidospru.usuariop a crm_app.users y asigna roles';

    public function handle(): int
    {
        $this->info('Migrando usuarios...');

        $usuarios = DB::connection('pedidosbcn')
            ->table('usuariop')
            ->select('*')
            ->get();

        $roles = DB::connection('pedidosbcn')
            ->table('usuariop_roles')
            ->pluck('nombre', 'id')
            ->toArray();

        $this->info("Encontrados: {$usuarios->count()} usuarios");

        if ($this->option('dry-run')) {
            foreach ($usuarios as $u) {
                $rolNombre = $roles[$u->id_rol] ?? 'operador';
                $this->line("  {$u->nombre} ({$u->usuario}) → Rol: {$rolNombre}");
            }
            return 0;
        }

        foreach ($usuarios as $u) {
            // Verificar si ya existe
            $exists = DB::table('users')->where('name', $u->nombre)->exists();
            if ($exists) {
                $this->warn("  Saltando: {$u->nombre} (ya existe)");
                continue;
            }

            $userId = DB::table('users')->insertGetId([
                'name' => $u->nombre,
                'email' => strtolower(str_replace(' ', '.', $u->nombre)) . '@bcnsoft.com.ar',
                'password' => Hash::make($u->clave), // Encriptar la clave legacy
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Asignar rol con Spatie (hay que hacerlo via modelo)
            // Por ahora indicamos cuál debería tener
            $rolNombre = $roles[$u->id_rol] ?? 'operador';
            $spatieRol = match (strtolower($rolNombre)) {
                'admin', 'administrador', 'socio' => 'socio',
                'vendedor' => 'vendedor',
                default => 'operador',
            };

            $this->info("  Migrado: {$u->nombre} → user_id: {$userId}, rol: {$spatieRol}");

            // NOTA: Para asignar el rol con Spatie, después de migrar ejecutar:
            // User::find($userId)->assignRole($spatieRol);
        }

        $this->info('');
        $this->info('IMPORTANTE: Después de migrar, ejecutar en tinker:');
        $this->info("  \$users = User::all();");
        $this->info("  // Asignar roles manualmente o con el UserManager del CRM");

        return 0;
    }
}
