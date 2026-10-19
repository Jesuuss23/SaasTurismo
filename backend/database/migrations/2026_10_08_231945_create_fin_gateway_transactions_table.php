<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_gateway_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('reservation_id')->constrained('booking_reservations')->cascadeOnDelete();
            $table->string('gateway', 30); // stripe, niubiz, culqi, mercadopago
            $table->string('transaction_id', 120); // ID externo devuelto por la pasarela
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 30)->default('pending'); // pending, completed, failed, refunded
            $table->string('card_brand', 30)->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'gateway', 'transaction_id']);
            $table->index(['tenant_id', 'reservation_id']);
            $table->index(['tenant_id', 'status']);
        });

        DB::statement('ALTER TABLE fin_gateway_transactions ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_gateway_txs ON fin_gateway_transactions USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_gateway_transactions');
    }
};