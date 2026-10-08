<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // REC-2026-0001
            $table->string('consumer_name', 160);
            $table->string('doc_type', 10)->default('DNI');
            $table->string('doc_number', 20);
            $table->string('email', 190);
            $table->string('phone', 40);
            $table->string('address', 200);
            $table->string('claim_type', 20)->default('queja'); // queja, reclamo
            $table->text('contracted_good_service');
            $table->text('detail');
            $table->text('consumer_request');
            $table->string('status', 20)->default('pendiente'); // pendiente, en_proceso, atendido
            $table->text('response_detail')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignUuid('responded_by')->nullable()->constrained('iam_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });

        DB::statement('ALTER TABLE crm_claims ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_crm_claims ON crm_claims USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_claims');
    }
};