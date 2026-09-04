<?php
// ================================================================
// MODELOS ELOQUENT - BCNSoft (conexión: bcnsoft)
// ================================================================

// ---- app/Models/BCNSoft/ConfigCliente.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;
use App\Enums\TaxCondition;

class ConfigCliente extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'config_clientes';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'mail', 'nombre', 'nomemp', 'razon_social', 'cuit', 'coniva',
        'direccion', 'localidad', 'provincia', 'telefono', 'activo', 'demo',
        'vto', 'pack', 'version', 'modalidad', 'codven', 'abono', 'setup',
        'saldo_setup', 'gan_abono', 'gan_setup', 'base', 'host', 'lComision',
        'comision', 'ultimo_pago', 'id_perfil', 'disc_iva', 'lAnualizado',
        'saldo_comision', 'responsable', 'fecha_inicio', 'id_rubro',
        'id_contacto_app', 'fecvto_certifi', 'baja', 'code', 'tel_contacto',
        'lic_principal', 'lic_adicional', 'lic_bonificada', 'es_manager',
        'implementacion_fecha', 'implementacion_personal',
        'implementacion_comanderas', 'implementacion_canales',
        'implementacion_paneles', 'tipo_facturacion', 'clave_fiscal',
        'tipo_conexion', 'lector_barras', 'instagram', 'facebook', 'pagina_web',
        'control_ult_pago', 'baselocal', 'panelweb', 'fechadesc', 'fecult',
        'tienda', 'limite_deuda', 'fecha_comision', 'tablas_creadas',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'demo' => 'boolean',
        'baja' => 'boolean',
        'lComision' => 'boolean',
        'lAnualizado' => 'boolean',
        'es_manager' => 'boolean',
        'lector_barras' => 'boolean',
        'baselocal' => 'boolean',
        'panelweb' => 'boolean',
        'tablas_creadas' => 'boolean',
        'vto' => 'date',
        'fecvto_certifi' => 'date',
        'ultimo_pago' => 'date',
        'fecha_inicio' => 'date',
        'implementacion_fecha' => 'date',
        'fechadesc' => 'date',
        'fecult' => 'date',
        'control_ult_pago' => 'date',
        'fecha_comision' => 'date',
        'abono' => 'decimal:2',
        'setup' => 'decimal:2',
        'comision' => 'decimal:2',
        'saldo_setup' => 'decimal:2',
        'saldo_comision' => 'decimal:2',
        'limite_deuda' => 'decimal:2',
    ];

    // ---- Relaciones ----

    public function contactos()
    {
        return $this->hasMany(ConfigClienteCont::class, 'id_cliente', 'id');
    }

    public function actividades()
    {
        return $this->hasMany(ConfigClienteActividad::class, 'id_cliente', 'id');
    }

    public function deudas()
    {
        return $this->hasMany(ConfigDeuda::class, 'id_cliente', 'id');
    }

    public function deudasLicencia()
    {
        return $this->hasMany(ConfigDeudaLic::class, 'id_cliente', 'id');
    }

    public function pagos()
    {
        return $this->hasMany(ConfigPago::class, 'id_cliente', 'id');
    }

    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'id_perfil', 'id');
    }

    public function rubro()
    {
        return $this->belongsTo(ConfigRubro::class, 'id_rubro', 'id');
    }

    // ---- Accessors ----

    public function getTipoPackAttribute(): string
    {
        return strtolower(substr($this->pack ?? '', 0, 2));
    }

    public function getPrefijoBaseAttribute(): string
    {
        $id = str_pad($this->id, 6, '0', STR_PAD_LEFT);
        return "{$this->base}.{$this->tipo_pack}_{$id}";
    }

    public function getCondicionIvaAttribute(): ?TaxCondition
    {
        return TaxCondition::tryFrom($this->coniva ?? 0);
    }

    public function getCondicionIvaTextoAttribute(): string
    {
        return $this->condicion_iva?->label() ?? 'Sin definir';
    }

    public function getTipoSistemaAttribute(): string
    {
        return $this->tipo_pack === 're' ? 'Gastronómico' : 'PyMes';
    }

    public function getTipoAppAttribute(): string
    {
        return $this->tipo_pack === 're' ? 'RESTO' : 'PYME';
    }

    public function getDeudaTotalAttribute(): float
    {
        return (float) $this->deudas()
            ->where('pago', false)
            ->sum('abono');
    }

    public function getDiasParaVencerAttribute(): int
    {
        if (!$this->vto) return 0;
        return (int) now()->diffInDays($this->vto, false);
    }

    public function getCertificadoPorVencerAttribute(): bool
    {
        if (!$this->fecvto_certifi) return false;
        return $this->fecvto_certifi->diffInDays(now()) < 30 || $this->fecvto_certifi->isPast();
    }

    // ---- Scopes ----

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('baja', false);
    }

    public function scopeDeBaja($query)
    {
        return $query->where('baja', true);
    }

    public function scopePorResponsable($query, int $responsableId)
    {
        return $query->where('responsable', $responsableId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return match ($tipo) {
            'pyme', 'ge' => $query->whereRaw("LEFT(pack,2) = 'GE'"),
            'resto', 're' => $query->whereRaw("LEFT(pack,2) = 'RE'"),
            default => $query,
        };
    }

    public function scopePorRubro($query, int $rubroId)
    {
        return $query->where('id_rubro', $rubroId);
    }

    public function scopeAnualizados($query)
    {
        return $query->where('lAnualizado', true)->activos();
    }

    public function scopeCertificadosPorVencer($query)
    {
        return $query->activos()
            ->whereNotNull('fecvto_certifi')
            ->whereRaw("(DATEDIFF(fecvto_certifi, CURDATE()) < 30 OR CURDATE() > fecvto_certifi)");
    }

    public function scopeConComision($query)
    {
        return $query->where('lComision', true);
    }
}

// ---- app/Models/BCNSoft/ConfigClienteCont.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class ConfigClienteCont extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'config_clientes_cont';
    public $timestamps = false;

    protected $fillable = ['id_cliente', 'nombre', 'telefono', 'fecha', 'observa'];
    protected $casts = ['fecha' => 'date'];

    public function cliente()
    {
        return $this->belongsTo(ConfigCliente::class, 'id_cliente', 'id');
    }
}

// ---- app/Models/BCNSoft/ConfigClienteActividad.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class ConfigClienteActividad extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'config_clientes_actividad';
    public $timestamps = false;

    protected $fillable = ['id_cliente', 'fecha', 'observa', 'usuario'];
    protected $casts = ['fecha' => 'date'];
}

// ---- app/Models/BCNSoft/ConfigDeuda.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class ConfigDeuda extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'config_deuda';
    public $timestamps = false;

    protected $fillable = [
        'cuota', 'id_cliente', 'fechavto', 'fechalimite', 'pack',
        'abono', 'gan_abono', 'pago', 'fechapago', 'formapago',
        'rendido', 'nro_pago', 'usuario',
    ];

    protected $casts = [
        'fechavto' => 'date',
        'fechalimite' => 'date',
        'fechapago' => 'date',
        'pago' => 'boolean',
        'rendido' => 'boolean',
        'abono' => 'decimal:2',
        'gan_abono' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(ConfigCliente::class, 'id_cliente', 'id');
    }

    public function scopePendientes($query)
    {
        return $query->where('pago', false);
    }

    public function scopeVencidas($query)
    {
        return $query->pendientes()->where('fechavto', '<', now());
    }
}

// ---- app/Models/BCNSoft/ConfigPago.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class ConfigPago extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'config_pagos';
    public $timestamps = false;

    protected $fillable = [
        'importe', 'formapag', 'fecha', 'recurrente', 'id_caja',
        'id_cliente', 'id_pedidosbcn', 'tienda_pedidosbcn', 'usuario',
        'por_comision', 'id_perfil', 'disc_iva', 'checkeado',
        'tipo_abono', 'responsable', 'del_responsable', 'lRendido',
    ];

    protected $casts = [
        'fecha' => 'date',
        'recurrente' => 'boolean',
        'por_comision' => 'boolean',
        'checkeado' => 'boolean',
        'lRendido' => 'boolean',
        'importe' => 'decimal:2',
        'del_responsable' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(ConfigCliente::class, 'id_cliente', 'id');
    }

    public function scopeSinCerrar($query)
    {
        return $query->whereNull('id_caja');
    }

    public function scopePorFormaPago($query, int $formapag)
    {
        return $query->where('formapag', $formapag);
    }
}

// ---- app/Models/BCNSoft/Perfil.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'perfiles';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'mp_token', 'cuit', 'coniva', 'cobrado',
        'cobrado_iva', 'cobros', 'activo', 'cbu', 'alias', 'telefono',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'cobrado' => 'decimal:2',
        'cobrado_iva' => 'decimal:2',
    ];
}

// ---- app/Models/BCNSoft/ConfigRubro.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class ConfigRubro extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'config_rubros';
    public $timestamps = false;
    protected $fillable = ['nombre'];
}

// ---- app/Models/BCNSoft/ConfigFormaPag.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class ConfigFormaPag extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'config_formapag';
    public $timestamps = false;
    protected $fillable = ['nombre'];
}

// ---- app/Models/BCNSoft/Abono.php ----
namespace App\Models\BCNSoft;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    protected $connection = 'bcnsoft';
    protected $table = 'abonos';
    protected $primaryKey = 'codigo';
    public $timestamps = false;
}


// ================================================================
// MODELOS ELOQUENT - PedidosBCN (conexión: pedidosbcn)
// ================================================================

// ---- app/Models/PedidosBCN/Comercio.php ----
namespace App\Models\PedidosBCN;

use Illuminate\Database\Eloquent\Model;
use App\Enums\TaxCondition;

class Comercio extends Model
{
    protected $connection = 'pedidosbcn';
    protected $table = 'comercios';
    public $timestamps = false;

    protected $fillable = [
        'tienda', 'nombre', 'razon_social', 'direccion', 'telefono',
        'email', 'activo', 'ciudad', 'cuit', 'coniva', 'tipo_abono',
        'fecvto', 'comision', 'abono', 'lComision', 'lAbonoFijo',
        'lAnualizado', 'ultimo_pago', 'id_perfil', 'disc_iva',
        'responsable', 'fecha_inicio', 'id_rubro', 'baja',
        'fecvto_certifi', 'clave_fiscal', 'limite_deuda',
        'instagram', 'facebook', 'pagina_web', 'saldo_a_favor',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'baja' => 'boolean',
        'lComision' => 'boolean',
        'lAbonoFijo' => 'boolean',
        'lAnualizado' => 'boolean',
        'fecvto' => 'date',
        'fecvto_certifi' => 'date',
        'ultimo_pago' => 'date',
        'fecha_inicio' => 'date',
        'comision' => 'decimal:2',
        'abono' => 'decimal:2',
        'limite_deuda' => 'decimal:2',
        'saldo_a_favor' => 'decimal:2',
    ];

    // ---- Relaciones ----

    public function contactos()
    {
        return $this->hasMany(ComercioCont::class, 'idcomercio', 'id');
    }

    public function pagos()
    {
        return $this->hasMany(ComercioPago::class, 'idcomercio', 'id');
    }

    // ---- Accessors ----

    public function getCondicionIvaAttribute(): ?TaxCondition
    {
        return TaxCondition::tryFrom($this->coniva ?? 0);
    }

    public function getDeudaTotalAttribute(): float
    {
        // Saldos pendientes de pedidos
        $tablaPedidos = "pedidos";
        return (float) \DB::connection('pedidosbcn')
            ->table($tablaPedidos)
            ->where('idcomercio', $this->id)
            ->where('saldo', '>', 0)
            ->where('estado', '<', 5)
            ->sum('saldo');
    }

    // ---- Scopes ----

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('baja', false);
    }

    public function scopePorResponsable($query, int $id)
    {
        return $query->where('responsable', $id);
    }
}

// ---- app/Models/PedidosBCN/ComercioCont.php ----
namespace App\Models\PedidosBCN;

use Illuminate\Database\Eloquent\Model;

class ComercioCont extends Model
{
    protected $connection = 'pedidosbcn';
    protected $table = 'comercios_cont';
    public $timestamps = false;

    protected $fillable = ['idcomercio', 'nombre', 'telefono'];
}

// ---- app/Models/PedidosBCN/ComercioPago.php ----
namespace App\Models\PedidosBCN;

use Illuminate\Database\Eloquent\Model;

class ComercioPago extends Model
{
    protected $connection = 'pedidosbcn';
    protected $table = 'comercios_pagos';
    public $timestamps = false;

    protected $fillable = [
        'idcomercio', 'fecha', 'importe', 'tipo_abono',
        'anual', 'codigo', 'tipo_pago', 'id_perfil',
    ];

    protected $casts = [
        'fecha' => 'date',
        'anual' => 'boolean',
        'importe' => 'decimal:2',
    ];
}


// ================================================================
// MODELOS ELOQUENT - CRM (conexión: mysql / default)
// ================================================================

// ---- app/Models/CRM/Ticket.php ----
namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use App\Enums\TicketType;
use App\Enums\TicketStatus;

class Ticket extends Model
{
    protected $fillable = [
        'client_id', 'system_source', 'client_name',
        'type', 'priority', 'status', 'channel',
        'liveconnect_chat_id', 'assigned_to',
        'subject', 'description', 'internal_notes',
        'payment_amount', 'payment_type',
        'due_date', 'first_response_at', 'resolved_at', 'closed_at',
        'created_by',
    ];

    protected $casts = [
        'type' => TicketType::class,
        'status' => TicketStatus::class,
        'due_date' => 'date',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'payment_amount' => 'decimal:2',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [TicketStatus::CERRADO, TicketStatus::RESUELTO]);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}

// ---- app/Models/CRM/Contact.php ----
namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ContactStatus;

class Contact extends Model
{
    protected $fillable = [
        'name', 'business_name', 'email', 'phone',
        'type', 'status', 'pack', 'client_id', 'system_source',
        'assigned_to', 'rubro_id', 'discard_reason_id', 'rubro_tienda',
        'notes', 'contact_date', 'contact_time', 'created_time',
        'liveconnect_id',
    ];

    protected $casts = [
        'status' => ContactStatus::class,
        'contact_date' => 'date',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function contactNotes()
    {
        return $this->hasMany(ContactNote::class)->orderByDesc('note_date');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [ContactStatus::DESCARTADO, ContactStatus::CONVERTIDO]);
    }
}

// ---- app/Models/CRM/DevTask.php ----
namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use App\Enums\DevTaskType;
use App\Enums\DevTaskStatus;

class DevTask extends Model
{
    protected $fillable = [
        'type', 'status', 'priority', 'title', 'client_name',
        'description', 'notes', 'client_description', 'show_to_client',
        'created_by', 'task_date', 'uploaded_date', 'estimated_date',
    ];

    protected $casts = [
        'type' => DevTaskType::class,
        'status' => DevTaskStatus::class,
        'show_to_client' => 'boolean',
        'task_date' => 'date',
        'uploaded_date' => 'date',
        'estimated_date' => 'date',
    ];

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(DevTaskRead::class);
    }

    public function scopePendientes($query)
    {
        return $query->where('status', '<', DevTaskStatus::PUBLICADO->value);
    }
}

// ---- app/Models/CRM/Onboarding.php ----
namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class Onboarding extends Model
{
    protected $fillable = [
        'client_id', 'system_source', 'client_name',
        'current_step', 'status', 'assigned_to',
        'started_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(OnboardingStep::class)->orderBy('step_number');
    }

    public function trainingSessions()
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByStep($query, int $step)
    {
        return $query->where('current_step', $step);
    }
}

// ---- app/Models/Shared/Usuariop.php ----
namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;

class Usuariop extends Model
{
    protected $connection = 'pedidosbcn';
    protected $table = 'usuariop';
    public $timestamps = false;
    protected $fillable = ['usuario', 'nombre', 'clave', 'id_rol', 'fijo'];
}
