<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cabecera de Cotizaciones (quote_quotes)
        Schema::create('quote_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 30); // COT-2026-00001
            $table->foreignUuid('client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignUuid('opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            $table->foreignUuid('owner_id')->constrained('iam_users');
            $table->string('title', 200);
            $table->string('status', 30)->default('borrador'); // borrador, enviada, vista, negociacion, aprobada, convertida, vencida, rechazada
            $table->char('currency', 3)->default('USD');
            $table->decimal('fx_rate', 12, 6)->default(3.750000);
            $table->date('valid_until');
            $table->smallInteger('pax')->default(1);
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('discount', 14, 2)->default(0.00);
            $table->decimal('discount_pct', 5, 2)->default(0.00);
            $table->decimal('tax', 14, 2)->default(0.00);
            $table->decimal('total', 14, 2)->default(0.00);
            $table->decimal('cost_total', 14, 2)->default(0.00);
            $table->decimal('profit', 14, 2)->default(0.00);
            $table->decimal('margin_pct', 6, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->integer('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'owner_id']);
            $table->index(['tenant_id', 'client_id']);
        });

        // 2. Líneas de Cotización (quote_quote_items)
        Schema::create('quote_quote_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('quote_id')->constrained('quote_quotes')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('catalog_products')->nullOnDelete();
            $table->foreignUuid('rate_id')->nullable()->constrained('catalog_rates')->nullOnDelete();
            $table->string('concept', 200);
            $table->date('service_date')->nullable();
            $table->smallInteger('pax')->default(1);
            $table->decimal('quantity', 8, 2)->default(1.00);
            $table->decimal('unit_cost', 14, 2)->default(0.00);
            $table->decimal('unit_price', 14, 2)->default(0.00);
            $table->decimal('total_cost', 14, 2)->default(0.00);
            $table->decimal('total_price', 14, 2)->default(0.00);
            $table->jsonb('snapshot')->nullable(); // Copia inmutable de la tarifa/producto
            $table->smallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'quote_id']);
        });

        // Habilitar RLS
        DB::statement('ALTER TABLE quote_quotes ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_quotes ON quote_quotes USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");

        DB::statement('ALTER TABLE quote_quote_items ENABLE ROW LEVEL SECURITY;');
        DB::statement("CREATE POLICY tenant_isolation_quote_items ON quote_quote_items USING (tenant_id = current_setting('app.tenant_id', true)::uuid);");
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_quote_items');
        Schema::dropIfExists('quote_quotes');
    }
};