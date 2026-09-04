# CLAUDE.md — CRM BCN (Instrucciones para Claude Code)

## ⚠️ REGLA #1: RESPETAR LO EXISTENTE

Este proyecto Laravel 12 ya tiene un sistema funcionando. El CRM es un **módulo nuevo que convive** con lo existente.

**ANTES de crear o modificar cualquier archivo:**
1. Explorá la estructura actual con `find` o `ls -R`
2. Revisá `routes/web.php` y `routes/api.php` existentes — NO sobreescribir, solo agregar
3. Revisá `config/database.php` — agregar conexiones sin borrar las existentes
4. Revisá `app/Models/` — crear modelos nuevos sin tocar los existentes
5. NUNCA borrar, renombrar o modificar archivos que no sean del CRM

**Prefijo de identificación:** Todos los archivos nuevos del CRM deben ser fácilmente identificables por su ubicación en subcarpetas específicas (ver estructura abajo).

---

## Descripción del CRM

Sistema CRM/ERP híbrido para empresa de desarrollo de software. Administra ~300 clientes distribuidos en dos sistemas (bcnsoft: 250 activos, pedidosbcn: 50 activos). Centraliza gestión de clientes, soporte técnico (vía LiveConnect/WhatsApp), facturación electrónica multi-CUIT (7 perfiles AFIP), contabilidad básica (ingresos/egresos), y reporting financiero mensual.

**Usuarios:** 8 personas con 3 roles (SOCIO, OPERADOR, VENDEDOR)
**Integraciones:** LiveConnect API (WhatsApp), AFIP SDK PHP (facturación), Mercado Pago API (7 cuentas)

---

## Stack del CRM

- Laravel 12 (el existente)
- Livewire 3 (componentes reactivos)
- Tailwind CSS (responsivo, mobile-first)
- Spatie Permission (roles: socio, operador, vendedor)
- SortableJS (Kanban drag & drop)
- Chart.js (gráficos del dashboard)
- DomPDF (facturas PDF)
- Maatwebsite/Laravel-Excel (importar/exportar)
- afip.php SDK (facturación electrónica ARCA/AFIP)

---

## Arquitectura de Base de Datos — 3 Conexiones

### Conexión `mysql` (default) — DB del proyecto existente + CRM
Las tablas del CRM se agregan a la DB principal mediante migraciones.
Tablas nuevas del CRM: `users` (extender si ya existe), `tickets`, `ticket_messages`, `contacts`, `contact_notes`, `contact_discard_reasons`, `dev_tasks`, `dev_task_reads`, `onboardings`, `onboarding_steps`, `training_sessions`, `billing_profiles`, `invoices`, `invoice_items`, `mp_renditions`, `monthly_closings`, `monthly_closing_details`, `activity_log`.

### Conexión `bcnsoft` (DB: bcn_config) — Legacy, Lectura + Escritura
**IMPORTANTE:** Esta DB ya existe y tiene datos en producción. No crear migraciones para ella. Solo acceder vía modelos Eloquent con `$connection = 'bcnsoft'`.

Tablas clave y su estructura:

**`config_clientes`** — Clientes BCNSoft (~250 activos)
- PK: `id` (int autoincrement)
- Identificación: `mail`, `nombre`, `nomemp` (nombre comercial), `razon_social`, `cuit`, `coniva` (1=RI, 2=NI, 3=NR, 4=Exento, 5=CF, 6=Monotributo)
- Dirección: `direccion`, `localidad`, `provincia`, `telefono`
- Sistema: `pack` (GE3=PyMes, RE3=Gastronómico), `version`, `base` (nombre DB del cliente), `host`
- Estado: `activo` (bool), `demo` (bool), `baja` (bool), `vto` (fecha vencimiento)
- Comercial: `codven`, `modalidad`, `fecventa`, `responsable` (FK a usuariop.id)
- Financiero: `abono` (monto), `setup`, `saldo_setup`, `gan_abono`, `gan_setup`, `comision` (%), `lComision` (bool), `saldo_comision`, `lAnualizado` (bool), `ultimo_pago`, `id_perfil` (FK a perfiles.id), `disc_iva` (1=sin IVA, 2=con IVA sin discriminar, 3=con IVA discriminado), `limite_deuda`
- Implementación: `implementacion_fecha`, `implementacion_personal`, `implementacion_comanderas`, `implementacion_canales`, `implementacion_paneles`, `tipo_facturacion` (1=FE, 2=Controlador, 3=Sin), `clave_fiscal`, `tipo_conexion`, `lector_barras`
- Licencias: `lic_principal`, `lic_adicional`, `lic_bonificada`, `es_manager` (bool)
- Redes: `instagram`, `facebook`, `pagina_web`
- LiveConnect: `id_contacto_app` (ID del contacto en LC)
- AFIP: `fecvto_certifi` (vencimiento certificado)
- Técnico: `code` (código acceso), `baselocal`, `panelweb`, `tablas_creadas`, `tienda`, `tel_contacto`

**`config_deuda`** — Cuotas de abono
- Clave compuesta: `cuota` (varchar "YYYY-MM") + `id_cliente`
- `abono` (monto), `gan_abono`, `pago` (bool), `fechapago`, `formapago`, `rendido` (bool), `nro_pago`, `fechavto`, `fechalimite`

**`config_pagos`** — Pagos recibidos
- PK: `id`. FK: `id_cliente`, `id_perfil`
- `importe`, `formapag` (FK config_formapag), `fecha`, `recurrente`, `id_caja` (NULL=sin cerrar)
- `id_pedidosbcn` y `tienda_pedidosbcn` (si el pago es de un cliente PedidosBCN)
- `por_comision`, `disc_iva`, `checkeado`, `tipo_abono`, `responsable`, `del_responsable`, `lRendido`

**`config_gastos`** — Gastos de la empresa
- `observa`, `fecha`, `hora`, `importe`, `id_ctacont`, `id_caja` (NULL=sin cerrar), `id_perfil`

**`config_cajas` / `config_cajas_det`** — Cierres de caja
- Cabecera: `fecha`, `hora`, `saldo`, `total`, `usuario`
- Detalle: `id_caja`, `tipo` (1=ingreso, 2=egreso), `concepto`, `importe`

**`config_movimientos`** — Ingresos y retiros manuales
- `tipo` (1=ingreso, 2=retiro), `fecha`, `hora`, `importe`, `observa`, `id_caja`, `id_perfil`

**`config_fondos`** — Fondos de caja
- `fecha`, `hora`, `importe`, `id_caja` (NULL=sin cerrar)

**`config_iva_acumulado`** — IVA acumulado del período
**`config_pagos_iva`** — IVA discriminado por pago
**`config_pagos_desc`** — Descuentos aplicados a pagos
**`config_pagos_rrhh`** — Pagos de sueldos
**`config_comisiones`** — Comisiones a responsables

**`config_ventas_encab`** — Facturas emitidas (legacy)
- `TICOMP`, `LETRA`, `NUMCOMP`, `CODCLI`, `FECHA`, `NETO`, `IVA`, `IMPORTE`, `cae`, `fecvto`, `id_pago`, `id_perfil`

**`contactos`** — Leads comerciales (migrados al CRM)
- `id_cliente` (si se convirtió), `nombre`, `nombre_com`, `email`, `telefono`
- `tipo` (1=PYME, 2=RESTO, 3=PedidosBCN), `estado` (1=Nuevo...5=Convertido...7=Descartado)
- `id_usuario` (responsable), `id_rubro`, `motivo_desc`, `observa`

**`contactos_det`** — Historial de seguimiento de leads

**`pendientes`** — Pendientes de DESARROLLO INTERNO (NO son tickets de clientes)
- `tipo` (1=Bug, 2=Mejora, 3=Funcionalidad, 4=Soporte), `estado` (1-6)
- `nombre`, `cliente` (texto), `descripcion`, `anotaciones`, `prioridad` (1-3)
- `lPoli`, `lAgus`, `lJuli`, `lPica`, `lValen` (checkboxes de lectura por persona)
- `descripcion_cli`, `lMostrar_cli` (visibilidad al cliente)
- `fecha_estimada`, `fecha_subido`

**`perfiles`** — Cuentas de Mercado Pago (7, una por CUIT)
- `nombre`, `mp_token`, `cuit`, `coniva`, `cobrado` (acumulado período), `cobrado_iva`, `cobros` (cantidad), `activo`, `cbu`, `alias`, `telefono`

**`perfiles_fact`** — Perfiles de facturación AFIP
- `nombre`, `cuit`, `direccion`, `localidad`, `ing_bruto`, `ini_acti`, `coniva`, `prefijo` (punto venta), `limite`, `sufijo`

**`abonos`** — Precios de abonos y licencias
- `precio` (mensual), `anual`, `seteo`, `comision`
- Licencias: `lic_principal_mensual`, `lic_adicional_mensual`, `lic_principal_anual`, `lic_adicional_anual` (y variantes `_manager` y `_semestral`)

**`config_packs`** — Tipos de sistema: GE3 (PyMes), RE3 (Gastronómico)
**`config_rubros`** — Rubros de comercios
**`config_formapag`** — Formas de pago (1=Efectivo, 2=Transferencia, 3=MP, 4=Tarjeta)
**`config_desc`** — Tipos de descuento
**`config_ctacont`** — Plan de cuentas contable
**`config_clientes_cont`** — Personas de contacto del cliente
**`config_clientes_actividad`** — Log de actividad por cliente
**`config_clientes_web`** — Config web del cliente (JSON)
**`aacontactos`** — Mapeo LiveConnect: `id`=id_LC, `extra1`=id_cliente
**`provee`** — Proveedores
**`compras`** / `compivadet` / `compretdet` — Compras y retenciones
**`config_usua`** — NO SE USA (legacy)

### Conexión `pedidosbcn` (DB: pedidospru) — Legacy, Lectura + Escritura

**`comercios`** — Clientes PedidosBCN (~50 activos)
- PK: `id`. Identifica: `tienda` (unique), `nombre`, `razon_social`, `cuit`, `coniva`
- Financiero: `tipo_abono`, `abono`, `comision`, `lComision`, `lAbonoFijo`, `lAnualizado`, `ultimo_pago`, `id_perfil`, `disc_iva`, `limite_deuda`, `saldo_a_favor`
- Estado: `activo`, `baja`, `fecvto`, `fecvto_certifi`
- `responsable`, `fecha_inicio`, `id_rubro`, `clave_fiscal`

**`comercios_cont`** — Personas de contacto del comercio
**`comercios_pagos`** — Historial de pagos: `idcomercio`, `fecha`, `importe`, `tipo_abono`, `anual`, `tipo_pago`, `id_perfil`
**`perfiles`** — Cuentas MP de PedidosBCN (estructura similar a bcnsoft)
**`abonos`** — Precios de abonos PedidosBCN
**`pedidos`** — Pedidos de delivery (campo `saldo` para deudas)
**`ventas_encab`** / `ventas_det`** — Ventas facturadas
**`cajas`** / `cajas_det`** — Cierres de caja de comercios
**`usuariop`** — Tabla de usuarios del CRM (8 personas): `usuario`, `nombre`, `clave`, `id_rol`, `fijo`
**`usuariop_roles`** / `usuariop_roles_det`** — Roles y permisos legacy
**`x_agenda`** — Capacitaciones programadas
**`x_guardias`** — Guardias de soporte/desarrollo
**`x_personas`** — Personal de la empresa

---

## Mapeo de Condición IVA

Los campos `coniva` en ambas DBs usan este mapeo:
```
1 = Responsable Inscripto (RI)
2 = No Inscripto
3 = IVA No Responsable
4 = Exento
5 = Consumidor Final (CF)
6 = Monotributo (MT)
```

Facturación: Emisor RI + Receptor RI → Factura A | Emisor RI + Otro → Factura B | Emisor MT → Factura C

---

## Lógica de Caja y Cierre (cómo funciona actualmente)

La caja funciona con un patrón de **`id_caja = NULL` = no cerrado**:
1. Todos los pagos, gastos, fondos, movimientos se crean con `id_caja = NULL`
2. Al cerrar turno: se crea registro en `config_cajas`, se obtiene su ID
3. Se actualiza `id_caja` en TODAS las tablas: config_pagos, config_pagos_desc, config_pagos_iva, config_gastos, config_fondos, config_movimientos, config_pagos_rrhh, config_iva_acumulado
4. Se resetean los acumulados en `perfiles`: `cobrado=0, cobrado_iva=0, cobros=0`
5. Se genera el detalle en `config_cajas_det` con los totales por concepto

---

## Integración LiveConnect (WhatsApp)

### API Endpoints
- Base URL: `https://api.liveconnect.chat/prod`
- Auth: POST `/account/token` con cKey + privateKey → devuelve PageGearToken
- Update: POST `/contacts/edt` con PageGearToken en header

### Campos Dinámicos (mapeo LC ↔ sistema)
```
dinamico5XRkX3  = rubro del comercio
dinamicoKebXw0  = tipo sistema (PYME/RESTO/PEDIDOS BCN)
dinamicoUHmNz4  = nombre comercial (nomemp)
dinamicoUoZap3  = razón social
dinamicoWW8jE5  = ID cliente en sistema
dinamicoyRZge3  = responsable asignado
dinamicoVTQod6  = CUIT
dinamicoG8qKM7  = clave fiscal
dinamicodOqOH8  = condición IVA (texto)
dinamicoVzBq89  = página web
dinamico4LtUi10 = instagram
dinamicoJaSJD11 = facebook
dinamicoKLnhC12 = licencias principales
dinamicopygcm13 = licencias adicionales
dinamicoeJnw014 = licencias bonificadas
dinamicoZULEv15 = fecha implementación
dinamico5L0xX17 = comanderas
dinamicoZXEJG18 = tipo facturación (texto)
dinamicocfL2d24 = tipo conexión
dinamiconMKcb24 = personal implementación
dinamico6HxRe25 = canales
dinamicoyKeWP25 = paneles
dinamicoltYGr25 = lector de barras
dinamicorCaMp25 = motivo descarte
```

Estos IDs van centralizados en `config/liveconnect.php`, NUNCA hardcodeados.

### Webhooks de LiveConnect
Reciben JSON con estructura `data.chat.contacto.dinamicos`. Los datos pueden tener unicode escapes (`\uXXXX`) que hay que decodificar.

---

## Roles y Permisos

| Función | SOCIO | OPERADOR | VENDEDOR |
|---------|:-----:|:--------:|:--------:|
| Dashboard completo | ✅ | Parcial | Parcial |
| Clientes (ver/editar) | ✅ | ✅ | ✅ |
| Onboarding Kanban | ✅ | ✅ | Solo ver |
| Tickets atención | ✅ | ✅ | ❌ |
| Dev tasks (pendientes) | ✅ | ✅ | ❌ |
| Gastos | ✅ | ✅ | ❌ |
| Facturación AFIP | ✅ | ❌ | ❌ |
| Cierre mensual | ✅ | ❌ | ❌ |
| Caja (ver) | ✅ | ✅ | ❌ |
| Deudas | ✅ | ✅ | Solo ver |
| Config CUITs/MP | ✅ | ❌ | ❌ |
| Admin usuarios | ✅ | ❌ | ❌ |

---

## Módulos del CRM

### 1. Dashboard
KPIs diferenciados por rol. Para SOCIOS: clientes activos, tickets abiertos, facturación mes, gastos, utilidad parcial, deuda total. Gráficos de ingreso vs egreso (6 meses) y distribución de ingresos.

### 2. Clientes (unificado bcnsoft + pedidosbcn)
Listado con tabs por sistema. Filtros: responsable, tipo (PYME/RESTO), rubro, estado, fechas. Ficha del cliente con pestañas: datos, contactos, onboarding, tickets, facturación, cuenta corriente, actividad. Lee directamente de las DBs legacy.

### 3. Onboarding (Kanban)
Tablero de 8 columnas arrastrables. Etapas: Datos Fiscales → Contactos → Perfil Técnico → Certificados AFIP → Excel Datos → Migración → Capacitación → Puesta en Marcha. Cada tarjeta muestra: comercio, sistema, días en etapa, responsable. Click abre modal con checklist.

### 4. Tickets (atención al cliente vía LiveConnect)
Tipos: consulta, reclamo, actualización, instalación impresora, nueva funcionalidad, aviso de pago, soporte técnico. Estados: abierto → en_curso → esperando_cliente → resuelto → cerrado. Hilo de mensajes. Integración webhooks LiveConnect.

### 5. Pendientes de Desarrollo (dev_tasks)
Tareas internas del equipo. Tipos: bug, mejora, nueva funcionalidad, soporte. Estados: pendiente → en desarrollo → en pruebas → esperando aprobación → publicado → cancelado. Marcas de lectura por usuario (reemplaza lPoli, lAgus, etc.).

### 6. Contactos / Leads
Pipeline de ventas. Estados: nuevo → contactado → interesado → en demo → convertido / descartado. Conversión a cliente desde aquí. Integrado con webhooks de LiveConnect.

### 7. Pagos e Ingresos
Formulario unificado para pagar abonos (mensual/anual/semestral), setup, licencias, comisiones. Calcula montos automáticamente según tipo y tabla de abonos. Discrimina IVA según `disc_iva` del cliente.

### 8. Facturación Electrónica (solo SOCIOS)
7 CUITs emisores. Auto-detección A/B/C. Emisión contra API AFIP → CAE. PDF. Notas de crédito/débito.

### 9. Gastos y Compras
Fiscales (con CUIT proveedor, CAE) y no fiscales. Categorización. Upload de comprobante.

### 10. Caja
Vista actual: ingresos y egresos acumulados del período sin cerrar. Cierre: consolida todo, marca con id_caja, resetea perfiles, genera detalle. Historial de cierres.

### 11. Cierre Mensual (solo SOCIOS)
Consolidación total: ingresos por tipo, egresos por categoría, rendimientos MP, utilidad neta. Exportable a PDF/Excel.

### 12. Deudas
Dos tabs: BCNSoft / PedidosBCN. Basado en `config_deuda` (cuotas impagas) y `pedidos.saldo>0`. Pagos parciales.

### 13. Mercado Pago
7 cuentas (1 por CUIT). Rendimientos mensuales: sum(transacciones) vs saldo_cierre = diferencia.

### 14. Personal
Personas, guardias, liquidaciones de responsables, sueldos.

---

## Estructura de Archivos del CRM (dónde va cada cosa)

```
app/
├── Enums/                              ← NUEVO: todos del CRM
│   ├── SystemSource.php
│   ├── TaxCondition.php
│   ├── TicketType.php
│   ├── TicketStatus.php
│   ├── OnboardingStep.php
│   ├── VoucherType.php
│   ├── PaymentMethod.php
│   ├── ContactStatus.php
│   ├── DevTaskType.php
│   └── DevTaskStatus.php
│
├── Models/
│   ├── (existentes — NO TOCAR)
│   ├── BCNSoft/                        ← NUEVO
│   │   ├── ConfigCliente.php
│   │   ├── ConfigClienteCont.php
│   │   ├── ConfigClienteActividad.php
│   │   ├── ConfigDeuda.php
│   │   ├── ConfigPago.php (+ PagoDesc, PagoIva)
│   │   ├── ConfigGasto.php
│   │   ├── ConfigCaja.php (+ CajaDet)
│   │   ├── ConfigFondo.php
│   │   ├── ConfigMovimiento.php
│   │   ├── Perfil.php
│   │   ├── PerfilFact.php
│   │   ├── Abono.php
│   │   ├── ConfigRubro.php
│   │   └── ...
│   ├── PedidosBCN/                     ← NUEVO
│   │   ├── Comercio.php
│   │   ├── ComercioPago.php
│   │   ├── Perfil.php
│   │   └── ...
│   ├── CRM/                            ← NUEVO
│   │   ├── Ticket.php
│   │   ├── TicketMessage.php
│   │   ├── Contact.php
│   │   ├── DevTask.php
│   │   ├── Onboarding.php
│   │   ├── OnboardingStep.php
│   │   ├── TrainingSession.php
│   │   ├── Invoice.php
│   │   ├── BillingProfile.php
│   │   ├── MpRendition.php
│   │   ├── MonthlyClosing.php
│   │   └── ActivityLog.php
│   └── Shared/                         ← NUEVO
│       ├── Usuariop.php
│       ├── XAgenda.php
│       └── XPersona.php
│
├── Services/                           ← NUEVO
│   ├── LiveConnectService.php
│   ├── AfipService.php
│   ├── PaymentService.php
│   ├── CashService.php
│   ├── DebtService.php
│   ├── MercadoPagoService.php
│   └── OnboardingService.php
│
├── Http/
│   ├── Controllers/
│   │   ├── (existentes — NO TOCAR)
│   │   └── Api/
│   │       └── LiveConnectWebhookController.php  ← NUEVO
│   ├── Livewire/                       ← NUEVO: todos los componentes CRM
│   │   ├── Dashboard/
│   │   ├── Clients/
│   │   ├── Onboarding/
│   │   ├── Tickets/
│   │   ├── DevTasks/
│   │   ├── Contacts/
│   │   ├── Payments/
│   │   ├── Invoicing/
│   │   ├── Expenses/
│   │   ├── Cash/
│   │   ├── Debts/
│   │   ├── MercadoPago/
│   │   ├── Personnel/
│   │   └── Admin/
│   └── Middleware/
│       └── (agregar si no existe CheckRole)
│
├── Jobs/                               ← NUEVO
│   ├── SyncLiveConnectContacts.php
│   ├── GenerateMonthlyDebts.php
│   └── CheckCertificateExpiry.php
│
├── Console/Commands/                   ← NUEVO
│   ├── MigrateContacts.php
│   ├── MigrateDevTasks.php
│   ├── MigrateLegacyInvoices.php
│   └── MigrateUsers.php
│
└── Policies/                           ← NUEVO
    ├── TicketPolicy.php
    ├── InvoicePolicy.php
    └── CashPolicy.php

config/
├── (existentes — NO TOCAR)
├── liveconnect.php                     ← NUEVO
└── database.php                        ← AGREGAR conexiones bcnsoft y pedidosbcn

routes/
├── web.php                             ← AGREGAR rutas del CRM al final, NO sobreescribir
└── api.php                             ← AGREGAR webhooks LiveConnect al final

resources/views/
├── (existentes — NO TOCAR)
├── livewire/                           ← NUEVO: vistas de componentes Livewire
└── components/                         ← NUEVO: componentes Blade reutilizables del CRM

database/migrations/
├── (existentes — NO TOCAR)
└── 2026_03_XX_XXXXXX_create_crm_*.php  ← NUEVO: migraciones del CRM
```

---

## Convenciones de Código

### Modelos Eloquent para DBs legacy
```php
class ConfigCliente extends Model
{
    protected $connection = 'bcnsoft';    // Siempre especificar
    protected $table = 'config_clientes'; // Nombre exacto
    public $timestamps = false;           // Las tablas legacy NO tienen timestamps
}
```

### Modelos Eloquent para tablas del CRM
```php
class Ticket extends Model
{
    // Sin $connection (usa default mysql)
    // Con $timestamps = true (las migraciones lo incluyen)
    protected $casts = [
        'type' => TicketType::class,      // Usar Enums
        'status' => TicketStatus::class,
    ];
}
```

### Rutas del CRM (agregar al final de routes/web.php)
```php
// ====================== CRM BCN ======================
Route::middleware(['auth', 'verified'])->prefix('crm')->name('crm.')->group(function () {
    // Todas las rutas del CRM van dentro de este grupo
    // con prefijo /crm/ para no colisionar con lo existente
});
```

### Componentes Livewire
Usar Full-Page Components con layout. Namespace: `App\Livewire\{Modulo}\{Componente}`.

### Consultas a DBs legacy
SIEMPRE usar Eloquent con el modelo apropiado. NUNCA hardcodear passwords de DB en el código (van en .env).

### Idioma
Interfaz y mensajes al usuario en ESPAÑOL. Código (variables, clases, métodos) en INGLÉS.

---

## Comandos Útiles

```bash
# Migrar solo tablas del CRM (no toca las legacy)
php artisan migrate

# Migrar datos históricos
php artisan migrate:users --dry-run
php artisan migrate:contacts --dry-run
php artisan migrate:dev-tasks --dry-run
php artisan migrate:invoices --dry-run

# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Crear componente Livewire
php artisan make:livewire Clients/ClientIndex
```

---

## Archivos de Referencia del Scaffold

Si necesitás consultar el diseño detallado, estos archivos contienen el plan completo:
- `docs/crm-scaffold/enums/AllEnums.php` — Todos los enums con sus métodos
- `docs/crm-scaffold/models/AllModels.php` — Todos los modelos con scopes y accessors
- `docs/crm-scaffold/migrations/all_migrations.php` — Todas las migraciones con estructura
- `docs/crm-scaffold/services/LiveConnectService.php` — Service refactorizado completo
- `docs/crm-scaffold/scripts/MigrationCommands.php` — Scripts de migración de datos
- `docs/crm-scaffold/config/liveconnect.php` — Config con campos dinámicos
- `docs/crm-scaffold/config/database.php` — 3 conexiones de DB
- `docs/crm-scaffold/routes/web_and_api.php` — Rutas completas

Copiar la carpeta `crm-scaffold/` a `docs/crm-scaffold/` en el proyecto para tener siempre la referencia accesible.
