<?php
// =============================================
// app/Enums/SystemSource.php
// =============================================
namespace App\Enums;

enum SystemSource: string
{
    case BCNSOFT = 'bcnsoft';
    case PEDIDOSBCN = 'pedidosbcn';

    public function label(): string
    {
        return match ($this) {
            self::BCNSOFT => 'BCNSoft',
            self::PEDIDOSBCN => 'PedidosBCN',
        };
    }

    public function connection(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::BCNSOFT => 'blue',
            self::PEDIDOSBCN => 'emerald',
        };
    }
}

// =============================================
// app/Enums/TaxCondition.php
// =============================================
namespace App\Enums;

enum TaxCondition: int
{
    case RESPONSABLE_INSCRIPTO = 1;
    case NO_INSCRIPTO = 2;
    case IVA_NO_RESPONSABLE = 3;
    case EXENTO = 4;
    case CONSUMIDOR_FINAL = 5;
    case MONOTRIBUTO = 6;

    public function label(): string
    {
        return match ($this) {
            self::RESPONSABLE_INSCRIPTO => 'Responsable Inscripto',
            self::NO_INSCRIPTO => 'No Inscripto',
            self::IVA_NO_RESPONSABLE => 'IVA No Responsable',
            self::EXENTO => 'Exento',
            self::CONSUMIDOR_FINAL => 'Consumidor Final',
            self::MONOTRIBUTO => 'Monotributo',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::RESPONSABLE_INSCRIPTO => 'RI',
            self::NO_INSCRIPTO => 'NI',
            self::IVA_NO_RESPONSABLE => 'NR',
            self::EXENTO => 'EX',
            self::CONSUMIDOR_FINAL => 'CF',
            self::MONOTRIBUTO => 'MT',
        };
    }

    /**
     * Determina tipo de comprobante según emisor y receptor
     * Emisor RI + Receptor RI  → A
     * Emisor RI + Receptor otro → B
     * Emisor MT + cualquiera   → C
     */
    public function voucherLetterFor(self $receptor): string
    {
        if ($this === self::MONOTRIBUTO) {
            return 'C';
        }

        return match ($receptor) {
            self::RESPONSABLE_INSCRIPTO => 'A',
            default => 'B',
        };
    }

    public static function fromLiveConnect(string $text): ?self
    {
        $map = config('liveconnect.condicion_iva_map', []);
        $id = $map[$text] ?? null;

        return $id ? self::tryFrom($id) : null;
    }
}

// =============================================
// app/Enums/TicketType.php
// =============================================
namespace App\Enums;

enum TicketType: string
{
    case CONSULTA = 'consulta';
    case RECLAMO = 'reclamo';
    case ACTUALIZACION = 'actualizacion';
    case INSTALACION_IMPRESORA = 'instalacion_impresora';
    case NUEVA_FUNCIONALIDAD = 'nueva_funcionalidad';
    case AVISO_PAGO = 'aviso_pago';
    case SOPORTE_TECNICO = 'soporte_tecnico';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTA => 'Consulta',
            self::RECLAMO => 'Reclamo',
            self::ACTUALIZACION => 'Actualización',
            self::INSTALACION_IMPRESORA => 'Instalación Impresora',
            self::NUEVA_FUNCIONALIDAD => 'Nueva Funcionalidad',
            self::AVISO_PAGO => 'Aviso de Pago',
            self::SOPORTE_TECNICO => 'Soporte Técnico',
            self::OTRO => 'Otro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CONSULTA => 'chat-bubble-left-right',
            self::RECLAMO => 'exclamation-triangle',
            self::ACTUALIZACION => 'arrow-path',
            self::INSTALACION_IMPRESORA => 'printer',
            self::NUEVA_FUNCIONALIDAD => 'light-bulb',
            self::AVISO_PAGO => 'banknotes',
            self::SOPORTE_TECNICO => 'wrench-screwdriver',
            self::OTRO => 'ellipsis-horizontal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CONSULTA => 'blue',
            self::RECLAMO => 'red',
            self::ACTUALIZACION => 'purple',
            self::INSTALACION_IMPRESORA => 'amber',
            self::NUEVA_FUNCIONALIDAD => 'green',
            self::AVISO_PAGO => 'emerald',
            self::SOPORTE_TECNICO => 'orange',
            self::OTRO => 'gray',
        };
    }
}

// =============================================
// app/Enums/TicketStatus.php
// =============================================
namespace App\Enums;

enum TicketStatus: string
{
    case ABIERTO = 'abierto';
    case EN_CURSO = 'en_curso';
    case ESPERANDO_CLIENTE = 'esperando_cliente';
    case RESUELTO = 'resuelto';
    case CERRADO = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::ABIERTO => 'Abierto',
            self::EN_CURSO => 'En Curso',
            self::ESPERANDO_CLIENTE => 'Esperando Cliente',
            self::RESUELTO => 'Resuelto',
            self::CERRADO => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ABIERTO => 'red',
            self::EN_CURSO => 'yellow',
            self::ESPERANDO_CLIENTE => 'orange',
            self::RESUELTO => 'green',
            self::CERRADO => 'gray',
        };
    }

    public function kanbanOrder(): int
    {
        return match ($this) {
            self::ABIERTO => 1,
            self::EN_CURSO => 2,
            self::ESPERANDO_CLIENTE => 3,
            self::RESUELTO => 4,
            self::CERRADO => 5,
        };
    }
}

// =============================================
// app/Enums/OnboardingStep.php
// =============================================
namespace App\Enums;

enum OnboardingStep: int
{
    case DATOS_FISCALES = 1;
    case CONTACTOS = 2;
    case PERFIL_TECNICO = 3;
    case CERTIFICADOS_AFIP = 4;
    case EXCEL_DATOS = 5;
    case MIGRACION = 6;
    case CAPACITACION = 7;
    case PUESTA_EN_MARCHA = 8;

    public function label(): string
    {
        return match ($this) {
            self::DATOS_FISCALES => 'Datos Fiscales',
            self::CONTACTOS => 'Contactos',
            self::PERFIL_TECNICO => 'Perfil Técnico',
            self::CERTIFICADOS_AFIP => 'Certificados AFIP',
            self::EXCEL_DATOS => 'Excel de Datos',
            self::MIGRACION => 'Migración',
            self::CAPACITACION => 'Capacitación',
            self::PUESTA_EN_MARCHA => 'Puesta en Marcha',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::DATOS_FISCALES => 'Fiscales',
            self::CONTACTOS => 'Contactos',
            self::PERFIL_TECNICO => 'Técnico',
            self::CERTIFICADOS_AFIP => 'AFIP',
            self::EXCEL_DATOS => 'Excel',
            self::MIGRACION => 'Migración',
            self::CAPACITACION => 'Capacitación',
            self::PUESTA_EN_MARCHA => 'En Marcha',
        };
    }

    public function checklist(): array
    {
        return match ($this) {
            self::DATOS_FISCALES => [
                'CUIT cargado',
                'Razón social verificada',
                'Condición IVA confirmada',
                'Domicilio fiscal registrado',
            ],
            self::CONTACTOS => [
                'Persona de contacto principal registrada',
                'Teléfono/WhatsApp verificado',
                'Email verificado',
                'Roles asignados (dueño, encargado, cajero)',
            ],
            self::PERFIL_TECNICO => [
                'Tipo de impresoras identificado',
                'Funcionalidades definidas',
                'Tipo de conexión definida',
                'Cantidad de licencias establecida',
            ],
            self::CERTIFICADOS_AFIP => [
                'Certificado de homologación generado',
                'Certificado de producción generado',
                'Punto de venta configurado',
            ],
            self::EXCEL_DATOS => [
                'Template de Excel enviado al cliente',
                'Excel devuelto por el cliente',
                'Datos validados',
            ],
            self::MIGRACION => [
                'Datos importados al sistema',
                'Validación post-migración completada',
                'Errores corregidos',
            ],
            self::CAPACITACION => [
                'Agenda de capacitación definida',
                'Al menos 1 persona capacitada',
                'Evaluación de capacitación completada',
            ],
            self::PUESTA_EN_MARCHA => [
                'Sistema funcionando en producción',
                'Cliente operando independientemente',
                'Primer factura emitida (si aplica)',
                'Fecha de go-live registrada',
            ],
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DATOS_FISCALES => 'slate',
            self::CONTACTOS => 'blue',
            self::PERFIL_TECNICO => 'indigo',
            self::CERTIFICADOS_AFIP => 'violet',
            self::EXCEL_DATOS => 'purple',
            self::MIGRACION => 'fuchsia',
            self::CAPACITACION => 'amber',
            self::PUESTA_EN_MARCHA => 'green',
        };
    }

    public static function all(): array
    {
        return self::cases();
    }
}

// =============================================
// app/Enums/VoucherType.php
// =============================================
namespace App\Enums;

enum VoucherType: string
{
    case FACTURA_A = 'FA';
    case FACTURA_B = 'FB';
    case FACTURA_C = 'FC';
    case NOTA_CREDITO_A = 'NCA';
    case NOTA_CREDITO_B = 'NCB';
    case NOTA_CREDITO_C = 'NCC';
    case NOTA_DEBITO_A = 'NDA';
    case NOTA_DEBITO_B = 'NDB';
    case NOTA_DEBITO_C = 'NDC';

    public function label(): string
    {
        return match ($this) {
            self::FACTURA_A => 'Factura A',
            self::FACTURA_B => 'Factura B',
            self::FACTURA_C => 'Factura C',
            self::NOTA_CREDITO_A => 'Nota de Crédito A',
            self::NOTA_CREDITO_B => 'Nota de Crédito B',
            self::NOTA_CREDITO_C => 'Nota de Crédito C',
            self::NOTA_DEBITO_A => 'Nota de Débito A',
            self::NOTA_DEBITO_B => 'Nota de Débito B',
            self::NOTA_DEBITO_C => 'Nota de Débito C',
        };
    }

    /**
     * Código AFIP del tipo de comprobante
     */
    public function afipCode(): int
    {
        return match ($this) {
            self::FACTURA_A => 1,
            self::FACTURA_B => 6,
            self::FACTURA_C => 11,
            self::NOTA_CREDITO_A => 3,
            self::NOTA_CREDITO_B => 8,
            self::NOTA_CREDITO_C => 13,
            self::NOTA_DEBITO_A => 2,
            self::NOTA_DEBITO_B => 7,
            self::NOTA_DEBITO_C => 12,
        };
    }

    public function isFactura(): bool
    {
        return in_array($this, [self::FACTURA_A, self::FACTURA_B, self::FACTURA_C]);
    }

    public function isNotaCredito(): bool
    {
        return in_array($this, [self::NOTA_CREDITO_A, self::NOTA_CREDITO_B, self::NOTA_CREDITO_C]);
    }

    public function letter(): string
    {
        return match ($this) {
            self::FACTURA_A, self::NOTA_CREDITO_A, self::NOTA_DEBITO_A => 'A',
            self::FACTURA_B, self::NOTA_CREDITO_B, self::NOTA_DEBITO_B => 'B',
            self::FACTURA_C, self::NOTA_CREDITO_C, self::NOTA_DEBITO_C => 'C',
        };
    }

    /**
     * Determina tipo de factura según emisor y receptor
     */
    public static function facturaFor(TaxCondition $emisor, TaxCondition $receptor): self
    {
        $letter = $emisor->voucherLetterFor($receptor);

        return match ($letter) {
            'A' => self::FACTURA_A,
            'B' => self::FACTURA_B,
            'C' => self::FACTURA_C,
        };
    }
}

// =============================================
// app/Enums/PaymentMethod.php
// =============================================
namespace App\Enums;

enum PaymentMethod: int
{
    case EFECTIVO = 1;
    case TRANSFERENCIA = 2;
    case MERCADO_PAGO = 3;
    case TARJETA = 4;

    public function label(): string
    {
        return match ($this) {
            self::EFECTIVO => 'Efectivo',
            self::TRANSFERENCIA => 'Transferencia',
            self::MERCADO_PAGO => 'Mercado Pago',
            self::TARJETA => 'Tarjeta',
        };
    }
}

// =============================================
// app/Enums/ContactStatus.php
// Mapea los estados de la tabla contactos (leads)
// =============================================
namespace App\Enums;

enum ContactStatus: int
{
    case NUEVO = 1;
    case CONTACTADO = 2;
    case INTERESADO = 3;
    case EN_DEMO = 4;
    case CONVERTIDO = 5;
    case SEGUIMIENTO = 6;
    case DESCARTADO = 7;

    public function label(): string
    {
        return match ($this) {
            self::NUEVO => 'Nuevo',
            self::CONTACTADO => 'Contactado',
            self::INTERESADO => 'Interesado',
            self::EN_DEMO => 'En Demo',
            self::CONVERTIDO => 'Convertido a Cliente',
            self::SEGUIMIENTO => 'En Seguimiento',
            self::DESCARTADO => 'Descartado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NUEVO => 'blue',
            self::CONTACTADO => 'cyan',
            self::INTERESADO => 'yellow',
            self::EN_DEMO => 'purple',
            self::CONVERTIDO => 'green',
            self::SEGUIMIENTO => 'orange',
            self::DESCARTADO => 'red',
        };
    }
}

// =============================================
// app/Enums/DevTaskType.php
// Para los pendientes de desarrollo (ex-pendientes)
// =============================================
namespace App\Enums;

enum DevTaskType: int
{
    case BUG = 1;
    case MEJORA = 2;
    case NUEVA_FUNCIONALIDAD = 3;
    case SOPORTE = 4;

    public function label(): string
    {
        return match ($this) {
            self::BUG => 'Bug / Error',
            self::MEJORA => 'Mejora',
            self::NUEVA_FUNCIONALIDAD => 'Nueva Funcionalidad',
            self::SOPORTE => 'Soporte',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BUG => 'red',
            self::MEJORA => 'blue',
            self::NUEVA_FUNCIONALIDAD => 'green',
            self::SOPORTE => 'yellow',
        };
    }
}

// =============================================
// app/Enums/DevTaskStatus.php
// =============================================
namespace App\Enums;

enum DevTaskStatus: int
{
    case PENDIENTE = 1;
    case EN_DESARROLLO = 2;
    case EN_PRUEBAS = 3;
    case ESPERANDO_APROBACION = 4;
    case PUBLICADO = 5;
    case CANCELADO = 6;

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_DESARROLLO => 'En Desarrollo',
            self::EN_PRUEBAS => 'En Pruebas',
            self::ESPERANDO_APROBACION => 'Esperando Aprobación',
            self::PUBLICADO => 'Publicado / Hecho',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDIENTE => 'gray',
            self::EN_DESARROLLO => 'blue',
            self::EN_PRUEBAS => 'purple',
            self::ESPERANDO_APROBACION => 'yellow',
            self::PUBLICADO => 'green',
            self::CANCELADO => 'red',
        };
    }
}
