<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Órdenes Operativas (ops_orders)
        Schema::create('ops_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // OP-2026-0001
            $table->foreignUuid('reservation_id')->constrained('booking_reservations')->cascadeOnDelete();
            $table->string('status', 30)->default('confirmada'); // confirmada, en_ejecucion, completada, cerrada, cancelada
            $table->date('execution_date');
            $table->text('notes')->nullable();
            $table->integer('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'execution_date']);
        });

        // 2. Servicios de la Orden Operativa (ops_services)
        Schema::create('ops_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('ops_orders')->cascadeOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('catalog_services')->nullOnDelete();
            $table->foreignUuid('provider_id')->nullable()->constrained('catalog_providers')->nullOnDelete();
            $table->string('name', 200);
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('meeting_point', 160)->nullable();
            $table->smallInteger('pax')->default(1);
            $table->string('voucher_code', 30)->nullable(); // VOU-2026-00001
            $table->string('status', 30)->default('pendiente'); // pendiente, asignado, confirmado, en_curso, completado, cancelado
            $table->decimal('cost', 14, 2)->default(0.00);
            $table->char('currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'status']);
        });

        // Activar RLS
        DB::statement('ALTER TABLE ops_orders ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_ops_orders ON ops_orders USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE ops_services ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_ops_services ON ops_services USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('ops_services');
        Schema::dropIfExists('ops_orders');
    }
};