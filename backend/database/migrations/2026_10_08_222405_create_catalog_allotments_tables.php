<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Contrato/Bloque de Cupo (catalog_allotments)
        Schema::create('catalog_allotments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // ALT-2026-001
            $table->string('name', 160);
            $table->foreignUuid('provider_id')->constrained('catalog_providers')->cascadeOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('catalog_services')->nullOnDelete();
            $table->string('type', 20)->default('asientos'); // asientos, habitaciones, cupos
            $table->smallInteger('release_days')->default(7); // Días antes de la fecha para liberar cupos no vendidos
            $table->date('date_from');
            $table->date('date_to');
            $table->string('status', 20)->default('activo'); // activo, inactivo, vencido
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'provider_id']);
            $table->index(['tenant_id', 'status']);
        });

        // 2. Inventario Diario por Fecha (catalog_allotment_dates)
        Schema::create('catalog_allotment_dates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('allotment_id')->constrained('catalog_allotments')->cascadeOnDelete();
            $table->date('date');
            $table->integer('allocated_qty')->default(0); // Total cupos bloqueados
            $table->integer('used_qty')->default(0);      // Cupos reservados/vendidos
            $table->integer('released_qty')->default(0);  // Cupos devueltos al proveedor tras fecha de release
            $table->decimal('unit_cost', 14, 2)->default(0.00);
            $table->string('status', 20)->default('abierto'); // abierto, cerrado, liberado
            $table->timestamps();

            $table->unique(['allotment_id', 'date']);
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'status']);
        });

        // Activar RLS
        DB::statement('ALTER TABLE catalog_allotments ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_allotments ON catalog_allotments USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE catalog_allotment_dates ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_allotment_dates ON catalog_allotment_dates USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_allotment_dates');
        Schema::dropIfExists('catalog_allotments');
    }
};