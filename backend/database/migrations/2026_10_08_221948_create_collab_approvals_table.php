<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collab_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // APR-2026-0001
            $table->string('type', 30); // descuento_cotizacion, margen_minimo, cancelacion_reserva, reembolso, credito_b2b
            $table->string('target_entity', 40); // Quote, Reservation, Payment
            $table->uuid('target_id');
            $table->foreignUuid('requester_id')->constrained('iam_users');
            $table->foreignUuid('approver_id')->nullable()->constrained('iam_users')->nullOnDelete();
            $table->string('status', 20)->default('pendiente'); // pendiente, aprobada, rechazada, expirada
            $table->text('reason'); // Motivo de la solicitud
            $table->text('resolution_notes')->nullable();
            $table->jsonb('snapshot')->nullable(); // Datos contextuales (margen actual, % descuento solicitado)
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'target_entity', 'target_id']);
        });

        DB::statement('ALTER TABLE collab_approvals ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_collab_approvals ON collab_approvals USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('collab_approvals');
    }
};