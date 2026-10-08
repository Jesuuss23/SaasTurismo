<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels_b2b_agencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // B2B-001
            $table->string('name', 160);
            $table->string('commercial_name', 160)->nullable();
            $table->string('tax_id', 30); // RUC o Tax ID internacional
            $table->string('country', 80)->default('Perú');
            $table->string('city', 80)->nullable();
            $table->string('contact_name', 120);
            $table->string('email', 190);
            $table->string('phone', 40)->nullable();
            
            // Condiciones comerciales B2B
            $table->decimal('commission_pct', 5, 2)->default(10.00); // % de comisión sobre venta
            $table->char('currency', 3)->default('USD');
            $table->decimal('credit_limit', 14, 2)->default(0.00);
            $table->decimal('credit_used', 14, 2)->default(0.00);
            $table->smallInteger('credit_days')->default(15);
            
            $table->string('status', 20)->default('activo'); // activo, suspendido, inactivo
            $table->foreignUuid('account_manager_id')->nullable()->constrained('iam_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'tax_id']);
            $table->index(['tenant_id', 'status']);
        });

        DB::statement('ALTER TABLE channels_b2b_agencies ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_b2b_agencies ON channels_b2b_agencies USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('channels_b2b_agencies');
    }
};