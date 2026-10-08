<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Correlativos (sys_sequences)
        Schema::create('sys_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('doc_type', 40); // quote, reservation, ops_order, voucher, invoice...
            $table->string('label', 60);
            $table->string('series', 10)->default('default');
            $table->string('prefix', 30);
            $table->smallInteger('pad_width')->default(5);
            $table->bigInteger('next_number')->default(1);
            $table->boolean('year_reset')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'doc_type', 'series']);
        });

        // 2. Tabla de Auditoría Inmutable (audit_events)
        Schema::create('audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('code')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->string('actor_type', 20)->default('user'); // user, platform_user, system
            $table->uuid('actor_id')->nullable();
            $table->string('actor_role', 60)->nullable();
            $table->string('entity', 40); // Lead, Cotizacion, Reserva, etc.
            $table->string('entity_id', 80);
            $table->string('action', 40); // login, crear, aprobar, cambio_estado...
            $table->boolean('is_critical')->default(false);
            $table->text('before_value')->nullable();
            $table->text('after_value')->nullable();
            $table->jsonb('diff')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 250)->nullable();
            $table->string('correlation_id', 40)->nullable();
            $table->jsonb('evidence')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['tenant_id', 'entity', 'entity_id']);
        });

        // Activar RLS
        DB::statement('ALTER TABLE sys_sequences ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_sequences ON sys_sequences USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE audit_events ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_audit ON audit_events USING (tenant_id IS NULL OR tenant_id = current_setting('app.tenant_id', true)::uuid);");

        // Trigger para evitar borrados o updates en audit_events (Inmutabilidad)
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_audit_tampering()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'audit_events es append-only: no se permite modificacion ni borrado';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_protect_audit_events
            BEFORE UPDATE OR DELETE ON audit_events
            FOR EACH ROW EXECUTE FUNCTION prevent_audit_tampering();
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS trg_protect_audit_events ON audit_events;");
        DB::statement("DROP FUNCTION IF EXISTS prevent_audit_tampering;");
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('sys_sequences');
    }
};