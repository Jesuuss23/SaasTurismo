<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cuentas por Pagar a Proveedores (fin_payables)
        Schema::create('fin_payables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // CXP-2026-0001
            $table->foreignUuid('provider_id')->constrained('catalog_providers')->cascadeOnDelete();
            $table->foreignUuid('ops_service_id')->nullable()->constrained('ops_services')->nullOnDelete();
            $table->string('concept', 160);
            $table->date('due_date');
            $table->char('currency', 3)->default('USD');
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('balance', 14, 2);
            $table->string('status', 20)->default('pendiente'); // pendiente, parcial, pagada, anulada
            $table->string('invoice_number', 50)->nullable(); // Factura emitida por el proveedor
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'provider_id']);
            $table->index(['tenant_id', 'status']);
        });

        // 2. Liquidaciones / Desembolsos a Proveedores (fin_disbursements)
        Schema::create('fin_disbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('payable_id')->constrained('fin_payables')->cascadeOnDelete();
            $table->string('code', 30); // DSP-2026-0001
            $table->string('method', 30); // Transferencia, Cheque, Efectivo
            $table->char('currency', 3)->default('USD');
            $table->decimal('amount', 14, 2);
            $table->string('bank_reference', 80)->nullable();
            $table->date('payment_date');
            $table->foreignUuid('paid_by')->constrained('iam_users');
            $table->string('status', 20)->default('aplicado');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'payable_id']);
        });

        DB::statement('ALTER TABLE fin_payables ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_payables ON fin_payables USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE fin_disbursements ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_disbursements ON fin_disbursements USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_disbursements');
        Schema::dropIfExists('fin_payables');
    }
};