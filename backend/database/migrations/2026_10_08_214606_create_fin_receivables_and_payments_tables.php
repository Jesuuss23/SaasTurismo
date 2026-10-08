<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cuentas por Cobrar (fin_receivables)
        Schema::create('fin_receivables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('reservation_id')->constrained('booking_reservations')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->string('concept', 160); // Anticipo 40%, Saldo 60%
            $table->date('due_date');
            $table->char('currency', 3)->default('USD');
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('balance', 14, 2);
            $table->string('status', 20)->default('pendiente'); // pendiente, parcial, pagada, vencida, anulada
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'reservation_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'due_date']);
        });

        // 2. Pagos Registrados (fin_payments)
        Schema::create('fin_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('receivable_id')->constrained('fin_receivables')->cascadeOnDelete();
            $table->string('code', 30); // REC-2026-00001
            $table->string('method', 30); // Transferencia, Tarjeta, Efectivo, Yape, Plin
            $table->char('currency', 3)->default('USD');
            $table->decimal('amount', 14, 2);
            $table->decimal('fx_rate', 12, 6)->default(3.750000);
            $table->string('transaction_reference', 80)->nullable();
            $table->date('payment_date');
            $table->foreignUuid('received_by')->constrained('iam_users');
            $table->string('status', 20)->default('aplicado'); // aplicado, revertido
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'receivable_id']);
            $table->index(['tenant_id', 'status']);
        });

        // Activar RLS
        DB::statement('ALTER TABLE fin_receivables ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_fin_receivables ON fin_receivables USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE fin_payments ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_fin_payments ON fin_payments USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_payments');
        Schema::dropIfExists('fin_receivables');
    }
};