<?php
// ================================================================
// MIGRACIONES PARA LA DB: crm_app
// Ejecutar en orden con: php artisan migrate
// ================================================================
// Cada sección es un archivo separado en database/migrations/
// ================================================================


// ================================================================
// 2026_03_01_000001_create_users_table.php
// (Laravel Breeze genera esta, pero la personalizamos)
// ================================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('phone', 50)->nullable();
            $table->string('avatar')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Spatie Permission crea sus propias tablas con:
        // php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
        // php artisan migrate
    }
};


// ================================================================
// 2026_03_01_000002_create_contacts_table.php
// Leads/Contactos comerciales (migrados desde bcn_config.contactos)
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // Datos del contacto
            $table->string('name', 100);
            $table->string('business_name', 200)->nullable();  // nombre_com
            $table->string('email', 100)->nullable();
            $table->string('phone', 100)->nullable();

            // Clasificación
            $table->tinyInteger('type')->default(0);           // 1=PYME, 2=RESTO, 3=PEDIDOS BCN
            $table->tinyInteger('status')->default(1);         // 1-7 (ContactStatus enum)
            $table->string('pack', 3)->nullable();

            // Relaciones
            $table->integer('client_id')->nullable();          // id_cliente si fue convertido
            $table->string('system_source')->nullable();       // bcnsoft/pedidosbcn
            $table->unsignedBigInteger('assigned_to')->nullable(); // id_usuario → users.id
            $table->integer('rubro_id')->nullable();           // id_rubro
            $table->integer('discard_reason_id')->nullable();  // motivo_desc
            $table->tinyInteger('rubro_tienda')->default(0);

            // Observaciones
            $table->text('notes')->nullable();                 // observa

            // Fechas originales
            $table->date('contact_date')->nullable();          // fechacont
            $table->time('contact_time')->nullable();          // horacont
            $table->time('created_time')->nullable();          // hora

            // LiveConnect
            $table->integer('liveconnect_id')->nullable();     // Para vincular con LC

            $table->timestamps();                               // fechacrea → created_at

            $table->index('status');
            $table->index('type');
            $table->index('assigned_to');
            $table->index('client_id');
        });

        // Detalle de seguimiento de contactos
        Schema::create('contact_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->date('note_date');
            $table->text('content')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        // Motivos de descarte
        Schema::create('contact_discard_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_notes');
        Schema::dropIfExists('contact_discard_reasons');
        Schema::dropIfExists('contacts');
    }
};


// ================================================================
// 2026_03_01_000003_create_tickets_table.php
// Tickets de atención al cliente (vía LiveConnect/WhatsApp)
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Cliente asociado
            $table->integer('client_id')->nullable();
            $table->string('system_source', 15)->nullable(); // bcnsoft/pedidosbcn
            $table->string('client_name', 255)->nullable();  // Cache

            // Clasificación
            $table->string('type', 30);                      // TicketType enum
            $table->string('priority', 10)->default('media');
            $table->string('status', 25)->default('abierto'); // TicketStatus enum

            // Origen
            $table->string('channel', 20)->default('liveconnect');
            $table->string('liveconnect_chat_id', 100)->nullable();

            // Asignación
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Contenido
            $table->string('subject', 255);
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();

            // Si es aviso de pago
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->string('payment_type', 50)->nullable();

            // Fechas
            $table->date('due_date')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('assigned_to');
            $table->index(['client_id', 'system_source']);
            $table->index('created_at');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_from_client')->default(false);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};


// ================================================================
// 2026_03_01_000004_create_dev_tasks_table.php
// Pendientes de desarrollo (migrados desde bcn_config.pendientes)
// NO son tickets de clientes, son tareas internas
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dev_tasks', function (Blueprint $table) {
            $table->id();

            $table->tinyInteger('type');                      // DevTaskType enum (1-4)
            $table->tinyInteger('status')->default(1);        // DevTaskStatus enum (1-6)
            $table->tinyInteger('priority')->default(1);      // 1=baja, 2=media, 3=alta

            $table->string('title', 100);                     // nombre
            $table->string('client_name', 200)->nullable();   // cliente (texto libre)
            $table->text('description')->nullable();           // descripcion
            $table->text('notes')->nullable();                 // anotaciones

            // Visibilidad al cliente (del sistema legacy)
            $table->text('client_description')->nullable();    // descripcion_cli
            $table->boolean('show_to_client')->default(false); // lMostrar_cli

            // Asignación
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Fechas
            $table->date('task_date');                         // fecha
            $table->date('uploaded_date')->nullable();         // fecha_subido
            $table->date('estimated_date')->nullable();        // fecha_estimada

            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('priority');
        });

        // Tabla para asignaciones de lectura por persona
        // Reemplaza los campos lPoli, lAgus, lJuli, lPica, lValen
        Schema::create('dev_task_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dev_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->unique(['dev_task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dev_task_reads');
        Schema::dropIfExists('dev_tasks');
    }
};


// ================================================================
// 2026_03_01_000005_create_onboarding_tables.php
// Pipeline de onboarding de nuevos clientes (NUEVO)
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboardings', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->string('system_source', 15);             // bcnsoft/pedidosbcn
            $table->string('client_name', 255)->nullable();  // Cache para kanban

            $table->tinyInteger('current_step')->default(1); // 1-8
            $table->string('status', 15)->default('in_progress');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['client_id', 'system_source']);
        });

        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('step_number');
            $table->string('step_name', 100);
            $table->string('status', 15)->default('pending');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->json('checklist')->nullable();            // Items con estado true/false
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->unique(['onboarding_id', 'step_number']);
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('client_id');
            $table->string('system_source', 15);
            $table->string('contact_name', 255)->nullable();
            $table->foreignId('trained_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('trained_by_2')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('training_date');
            $table->string('time_from', 5)->nullable();       // Hora inicio (HH:mm)
            $table->string('time_to', 5)->nullable();          // Hora fin
            $table->integer('duration_minutes')->nullable();
            $table->string('topic', 150)->nullable();          // motivo
            $table->json('topics')->nullable();
            $table->string('status', 15)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('onboarding_steps');
        Schema::dropIfExists('onboardings');
    }
};


// ================================================================
// 2026_03_01_000006_create_invoices_table.php
// Facturación electrónica multi-CUIT
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        // Perfiles de facturación del CRM
        // (complementa perfiles_fact de bcn_config)
        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('cuit', 13);
            $table->string('business_name', 255);
            $table->tinyInteger('tax_condition');              // TaxCondition enum
            $table->string('address', 255)->nullable();
            $table->string('locality', 100)->nullable();
            $table->string('ingresos_brutos', 13)->nullable();
            $table->date('activity_start')->nullable();

            // AFIP config
            $table->string('afip_certificate_path', 500)->nullable();
            $table->string('afip_key_path', 500)->nullable();
            $table->boolean('afip_production')->default(false);
            $table->integer('point_of_sale');
            $table->string('suffix', 10)->nullable();         // sufijo del legacy

            // Cuenta bancaria
            $table->string('cbu', 22)->nullable();
            $table->string('alias', 50)->nullable();
            $table->string('phone', 20)->nullable();

            // Mercado Pago asociado
            $table->string('mp_token', 150)->nullable();

            // Control de facturación (del legacy)
            $table->decimal('billing_limit', 12, 2)->default(0);

            // Acumulados del período actual (se resetean al cerrar)
            $table->decimal('collected', 10, 2)->default(0);     // cobrado
            $table->decimal('collected_iva', 10, 2)->default(0); // cobrado_iva
            $table->integer('collection_count')->default(0);     // cobros

            $table->boolean('is_active')->default(true);
            $table->integer('legacy_perfil_id')->nullable();     // FK a perfiles.id
            $table->integer('legacy_perfilfact_id')->nullable(); // FK a perfiles_fact.id
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained();

            // Cliente
            $table->integer('client_id');
            $table->string('system_source', 15);
            $table->string('client_name', 255)->nullable();
            $table->string('client_cuit', 13)->nullable();
            $table->tinyInteger('client_tax_condition')->nullable();
            $table->string('client_address', 255)->nullable();
            $table->string('client_locality', 100)->nullable();
            $table->string('client_email', 100)->nullable();

            // Comprobante
            $table->string('voucher_type', 5);               // VoucherType enum value
            $table->integer('point_of_sale');
            $table->integer('voucher_number');

            // AFIP
            $table->string('cae', 14)->nullable();
            $table->date('cae_expiration')->nullable();
            $table->string('afip_result', 10)->nullable();

            // Montos
            $table->decimal('net_amount', 12, 2);
            $table->decimal('iva_amount', 12, 2)->default(0);
            $table->decimal('iibb_amount', 12, 2)->default(0);
            $table->decimal('perception_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);

            // Concepto
            $table->tinyInteger('concept')->default(2);       // 1=prod, 2=serv, 3=ambos
            $table->date('service_from')->nullable();
            $table->date('service_to')->nullable();
            $table->date('payment_due_date')->nullable();
            $table->text('description')->nullable();

            // Estado
            $table->string('status', 15)->default('draft');

            // Pago vinculado
            $table->boolean('payment_received')->default(false);
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->integer('legacy_pago_id')->nullable();     // FK a config_pagos.id

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index('billing_profile_id');
            $table->index(['client_id', 'system_source']);
            $table->index('status');
            $table->index('issued_at');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('iva_rate', 5, 2)->default(21.00);
            $table->decimal('subtotal', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('billing_profiles');
    }
};


// ================================================================
// 2026_03_01_000007_create_mp_renditions_table.php
// Rendimientos de Mercado Pago por cuenta y período
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_renditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained();
            $table->string('profile_name', 100)->nullable();
            $table->tinyInteger('period_month');
            $table->smallInteger('period_year');

            $table->decimal('total_transactions', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->default(0);
            $table->decimal('rendition', 14, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['billing_profile_id', 'period_month', 'period_year'], 'mp_renditions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_renditions');
    }
};


// ================================================================
// 2026_03_01_000008_create_monthly_closings_table.php
// Cierres mensuales consolidados
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_closings', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('month');
            $table->smallInteger('year');

            // Totales
            $table->decimal('total_income', 14, 2)->default(0);
            $table->decimal('total_expenses', 14, 2)->default(0);
            $table->decimal('total_mp_renditions', 14, 2)->default(0);
            $table->decimal('total_iva', 14, 2)->default(0);
            $table->decimal('total_commissions', 14, 2)->default(0);
            $table->decimal('total_salaries', 14, 2)->default(0);
            $table->decimal('total_funds', 14, 2)->default(0);
            $table->decimal('net_profit', 14, 2)->default(0);

            // Desgloses en JSON
            $table->json('income_by_type')->nullable();
            $table->json('income_by_profile')->nullable();
            $table->json('income_by_payment_method')->nullable();
            $table->json('expenses_by_category')->nullable();

            // Referencia al cierre de caja legacy
            $table->integer('legacy_caja_id')->nullable();

            $table->string('status', 10)->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['month', 'year']);
        });

        // Detalle del cierre (reemplaza config_cajas_det)
        Schema::create('monthly_closing_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_closing_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('type');                      // 1=ingreso, 2=egreso
            $table->string('concept', 100);
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('billing_profile_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_closing_details');
        Schema::dropIfExists('monthly_closings');
    }
};


// ================================================================
// 2026_03_01_000009_create_activity_log_table.php
// ================================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 100)->nullable();
            $table->string('action', 100);                    // 'client.updated', etc.
            $table->string('model_type', 100)->nullable();
            $table->string('model_id', 50)->nullable();
            $table->string('system_source', 15)->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();           // old/new values
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
