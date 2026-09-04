# CRM BCN - Scaffold de Proyecto

## Estructura de archivos entregados

```
crm-scaffold/
├── config/
│   ├── .env.example          → Variables de entorno (3 DBs, LiveConnect, AFIP, MP)
│   ├── database.php          → 3 conexiones: mysql (CRM), bcnsoft, pedidosbcn
│   └── liveconnect.php       → Config con mapeo de campos dinámicos
│
├── enums/
│   └── AllEnums.php          → Todos los Enums PHP 8.1+
│       ├── SystemSource      → bcnsoft / pedidosbcn
│       ├── TaxCondition      → Condición IVA (1-6) con auto-determinación A/B/C
│       ├── TicketType         → Tipos de ticket de atención
│       ├── TicketStatus       → Estados de ticket
│       ├── OnboardingStep     → 8 pasos con checklists
│       ├── VoucherType        → FA, FB, FC, NCA... con códigos AFIP
│       ├── PaymentMethod      → Formas de pago
│       ├── ContactStatus      → Estados de lead (1-7)
│       ├── DevTaskType        → Tipos de pendiente de desarrollo
│       └── DevTaskStatus      → Estados de pendiente
│
├── migrations/
│   └── all_migrations.php    → Todas las migraciones del CRM
│       ├── users              → Con Spatie Permission
│       ├── contacts           → Migrado de bcn_config.contactos
│       ├── contact_notes      → Migrado de bcn_config.contactos_det
│       ├── tickets            → Tickets de atención al cliente (LiveConnect)
│       ├── dev_tasks          → Pendientes de desarrollo (ex-pendientes)
│       ├── dev_task_reads     → Reemplaza lPoli, lAgus, etc.
│       ├── onboardings        → Pipeline de onboarding (NUEVO)
│       ├── onboarding_steps   → Pasos con checklists
│       ├── training_sessions  → Capacitaciones
│       ├── billing_profiles   → 7 CUITs unificados
│       ├── invoices           → Facturación electrónica
│       ├── invoice_items      → Detalle de facturas
│       ├── mp_renditions      → Rendimientos Mercado Pago
│       ├── monthly_closings   → Cierres mensuales
│       ├── monthly_closing_details
│       └── activity_log       → Auditoría general
│
├── models/
│   └── AllModels.php         → Todos los modelos Eloquent
│       ├── BCNSoft/ConfigCliente   → config_clientes (con scopes, accessors)
│       ├── BCNSoft/ConfigDeuda     → config_deuda
│       ├── BCNSoft/ConfigPago      → config_pagos
│       ├── BCNSoft/Perfil          → perfiles (MP)
│       ├── PedidosBCN/Comercio     → comercios
│       ├── PedidosBCN/ComercioPago → comercios_pagos
│       ├── CRM/Ticket              → Tickets de atención
│       ├── CRM/Contact             → Leads/contactos migrados
│       ├── CRM/DevTask             → Pendientes de desarrollo
│       ├── CRM/Onboarding          → Pipeline onboarding
│       └── Shared/Usuariop         → Usuarios legacy
│
├── services/
│   └── LiveConnectService.php → REFACTOR PRINCIPAL
│       ├── getToken()          → Reemplaza 6+ curls repetidos
│       ├── updateContact()     → Centraliza /contacts/edt
│       ├── handleContactCreated()    → API_contacto_crear
│       ├── handleContactDiscarded()  → API_contacto_descartado
│       ├── handleClientCreation()    → API_contacto_altaCliente
│       ├── handleIdentifyClient()    → API_identifica_contacto
│       └── syncAllBcnsoftContacts()  → API_actualizar_contactos
│
├── livewire/
│   └── LiveConnectWebhookController.php → Controller limpio para webhooks
│
├── routes/
│   └── web_and_api.php       → Todas las rutas (web + API webhooks)
│
├── scripts/
│   └── MigrationCommands.php → Scripts de migración de datos
│       ├── migrate:users      → usuariop → users
│       ├── migrate:contacts   → contactos → contacts + contact_notes
│       ├── migrate:dev-tasks  → pendientes → dev_tasks
│       └── migrate:invoices   → config_ventas_encab → invoices + billing_profiles
│
└── README.md                 → Este archivo
```

## Pasos para iniciar el proyecto

### 1. Crear proyecto Laravel 12
```bash
composer create-project laravel/laravel crm-bcn
cd crm-bcn
```

### 2. Instalar dependencias
```bash
composer require livewire/livewire
composer require spatie/laravel-permission
composer require afipsdk/afip.php
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
composer require intervention/image

# Dev
composer require laravel/breeze --dev
php artisan breeze:install livewire

# NPM
npm install sortablejs chart.js
npm run build
```

### 3. Configurar .env
Copiar `.env.example` y completar las 3 conexiones de DB + credenciales

### 4. Copiar archivos del scaffold
- Separar cada enum en su propio archivo en `app/Enums/`
- Separar cada modelo en su propio archivo en `app/Models/`
- Separar cada migración en su propio archivo en `database/migrations/`
- Copiar configs a `config/`
- Copiar services a `app/Services/`
- Copiar routes a `routes/`
- Copiar commands a `app/Console/Commands/`

### 5. Ejecutar migraciones
```bash
# Publicar Spatie
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Migrar DB del CRM
php artisan migrate

# Crear roles
php artisan tinker
>>> use Spatie\Permission\Models\Role;
>>> Role::create(['name' => 'socio']);
>>> Role::create(['name' => 'operador']);
>>> Role::create(['name' => 'vendedor']);
```

### 6. Migrar datos históricos
```bash
php artisan migrate:users --dry-run     # Verificar primero
php artisan migrate:users

php artisan migrate:contacts --dry-run
php artisan migrate:contacts

php artisan migrate:dev-tasks --dry-run
php artisan migrate:dev-tasks

php artisan migrate:invoices --dry-run
php artisan migrate:invoices
```

### 7. Asignar roles a usuarios
```bash
php artisan tinker
>>> $user = User::where('name', 'NombreSocio')->first();
>>> $user->assignRole('socio');
```

## Notas importantes

1. **Los archivos con "All" en el nombre** contienen múltiples clases/migraciones
   que deben separarse en archivos individuales antes de usar.

2. **Las DBs legacy (bcnsoft, pedidosbcn) no se modifican** con migraciones.
   Los modelos Eloquent apuntan directamente a las tablas existentes.

3. **El LiveConnectService** es el refactor más grande. Reemplaza ~2000 líneas
   de código repetitivo en Web.php con ~400 líneas limpias y testeables.

4. **Los campos dinámicos de LiveConnect** están centralizados en
   `config/liveconnect.php`. Si LC cambia algún ID, solo se modifica ahí.
