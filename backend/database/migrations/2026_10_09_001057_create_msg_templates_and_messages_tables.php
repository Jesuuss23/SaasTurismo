<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Plantillas de Mensajes (msg_templates)
        Schema::create('msg_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 40); // TPL-RESERVATION-CONFIRMATION, TPL-VOUCHER-ISSUED
            $table->string('name', 120);
            $table->string('channel', 20); // email, whatsapp, sms
            $table->string('subject', 160)->nullable(); // Requerido para email
            $table->text('body_template'); // Plantilla con variables {{client_name}}, {{code}}, etc.
            $table->jsonb('variables')->nullable(); // Metadata de variables disponibles
            $table->string('status', 20)->default('activo'); // activo, inactivo
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'channel']);
            $table->index(['tenant_id', 'status']);
        });

        // 2. Mensajes Despachados / Cola de Salida (msg_messages)
        Schema::create('msg_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('msg_templates')->nullOnDelete();
            $table->string('channel', 20); // email, whatsapp
            $table->string('recipient_to', 160); // email o número telefónico E.164 (+519...)
            $table->string('recipient_name', 160)->nullable();
            $table->string('subject', 160)->nullable();
            $table->text('content');
            $table->string('status', 20)->default('pendiente'); // pendiente, enviado, entregado, leido, fallido
            $table->string('external_id', 120)->nullable(); // ID retornado por SendGrid / Meta WhatsApp API
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'channel']);
            $table->index(['tenant_id', 'recipient_to']);
        });

        DB::statement('ALTER TABLE msg_templates ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_msg_templates ON msg_templates USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE msg_messages ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_msg_messages ON msg_messages USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('msg_messages');
        Schema::dropIfExists('msg_templates');
    }
};